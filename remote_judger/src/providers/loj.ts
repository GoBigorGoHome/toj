import superagent from 'superagent';
import proxy from 'superagent-proxy';
import sleep from '../utils/sleep';
import { IBasicProvider, RemoteAccount, USER_AGENT } from '../interface';
import Logger from '../utils/logger';

proxy(superagent);
const logger = new Logger('remote/loj');

const langs_map = {
  C: {
    name: 'C (gcc, c11, O2, m64)',
    info: {
      language: 'c',
      compileAndRunOptions: {
        compiler: 'gcc',
        O: '2',
        m: '64',
        std: 'c11',
      },
    },
    comment: '//',
  },
  'C++03': {
    name: 'C++ (g++, c++03, O2, m64)',
    info: {
      language: 'cpp',
      compileAndRunOptions: {
        compiler: 'g++',
        std: 'c++03',
        O: '2',
        m: '64',
      },
    },
    comment: '//',
  },
  'C++11': {
    name: 'C++ (g++, c++11, O2, m64)',
    info: {
      language: 'cpp',
      compileAndRunOptions: {
        compiler: 'g++',
        std: 'c++11',
        O: '2',
        m: '64',
      },
    },
    comment: '//',
  },
  'C++': {
    name: 'C++ (g++, c++14, O2, m64)',
    info: {
      language: 'cpp',
      compileAndRunOptions: {
        compiler: 'g++',
        std: 'c++14',
        O: '2',
        m: '64',
      },
    },
    comment: '//',
  },
  'C++17': {
    name: 'C++ (g++, c++17, O2, m64)',
    info: {
      language: 'cpp',
      compileAndRunOptions: {
        compiler: 'g++',
        std: 'c++17',
        O: '2',
        m: '64',
      },
    },
    comment: '//',
  },
  'C++20': {
    name: 'C++ (g++, c++20, O2, m64)',
    info: {
      language: 'cpp',
      compileAndRunOptions: {
        compiler: 'g++',
        std: 'c++20',
        O: '2',
        m: '64',
      },
    },
    comment: '//',
  },
  'Python2.7': {
    name: 'Python (2.7)',
    info: {
      language: 'python',
      compileAndRunOptions: {
        version: '2.7',
      },
    },
    comment: '#',
  },
  Python3: {
    name: 'Python (3.10)',
    info: {
      language: 'python',
      compileAndRunOptions: {
        version: '3.10',
      },
    },
    comment: '#',
  },
  Java17: {
    name: 'Java',
    info: {
      language: 'java',
      compileAndRunOptions: {},
    },
    comment: '//',
  },
  Pascal: {
    name: 'Pascal',
    info: {
      language: 'pascal',
      compileAndRunOptions: {
        O: '2',
      },
    },
    comment: '//',
  },
};

export function getAccountInfoFromEnv(): RemoteAccount | null {
  const {
    LOJ_HANDLE,
    LOJ_TOKEN,
    LOJ_ENDPOINT = 'https://api.loj.ac.cn/api',
    LOJ_PROXY,
  } = process.env;

  if (!LOJ_TOKEN) return null;

  const account: RemoteAccount = {
    type: 'loj',
    handle: LOJ_HANDLE,
    password: LOJ_TOKEN,
    endpoint: LOJ_ENDPOINT,
  };

  if (LOJ_PROXY) account.proxy = LOJ_PROXY;

  return account;
}

export default class LibreojProvider implements IBasicProvider {
  constructor(public account: RemoteAccount) {
    this.account.endpoint ||= 'https://api.loj.ac.cn/api';
  }

  get(url: string) {
    logger.debug('get', url);
    if (!url.includes('//')) url = `${this.account.endpoint}${url}`;
    const req = superagent
      .get(url)
      .auth(this.account.password, { type: 'bearer' })
      .set('User-Agent', USER_AGENT);
    if (this.account.proxy) return req.proxy(this.account.proxy);
    return req;
  }

  post(url: string) {
    logger.debug('post', url);
    if (!url.includes('//')) url = `${this.account.endpoint}${url}`;
    const req = superagent
      .post(url)
      .type('json')
      .auth(this.account.password, { type: 'bearer' })
      .set('User-Agent', USER_AGENT)
      .set('x-recaptcha-token', 'skip');
    if (this.account.proxy) return req.proxy(this.account.proxy);
    return req;
  }

  get loggedIn() {
    return this.get('/auth/getSessionInfo?token=' + this.account.password).then(
      res =>
        res.body.userMeta && res.body.userMeta.username === this.account.handle
    );
  }

  async ensureLogin() {
    if (await this.loggedIn) return true;
    logger.info('retry login');
    // TODO: login
    return false;
  }

  async getProblemId(displayId: number) {
    const { body } = await this.post('/problem/getProblem').send({ displayId });

    return body.meta.id;
  }

  async submitProblem(
    displayId: string,
    lang: string,
    code: string,
    submissionId: number,
    next,
    end
  ) {
    const programType = langs_map[lang] || langs_map['C++'];
    const comment = programType.comment;

    if (comment) {
      const msg = `S2OJ Submission #${submissionId} @ ${new Date().getTime()}`;
      if (typeof comment === 'string') code = `${comment} ${msg}\n${code}`;
      else if (comment instanceof Array)
        code = `${comment[0]} ${msg} ${comment[1]}\n${code}`;
    }

    const id = await this.getProblemId(parseInt(displayId));

    logger.debug(
      'Submitting',
      id,
      `(displayId: ${displayId})`,
      programType,
      lang,
      `(S2OJ Submission #${submissionId})`
    );

    const { body, error } = await this.post('/submission/submit').send({
      problemId: id,
      content: {
        code,
        ...programType.info,
      },
      uploadInfo: null,
    });

    if (error) {
      await end({
        error: true,
        status: 'Judgment Failed',
        message: 'Failed to submit code.',
      });

      return null;
    }

    return body.submissionId;
  }

  async waitForSubmission(problem_id: string, id: string, next, end) {
    let i = 0;

    while (true) {
      if (++i > 60) {
        return await end({
          id,
          error: true,
          status: 'Judgment Failed',
          message: 'Failed to fetch submission details.',
        });
      }

      await sleep(2000);
      const { body, error } = await this.post('/submission/getSubmissionDetail')
        .send({ submissionId: String(id), locale: 'zh_CN' })
        .retry(3);

      if (error) continue;

      if (body.progress.progressType !== 'Finished') {
        await next({
          status: `${body.progress.progressType}`,
        });

        continue;
      }

      if (body.meta.status === 'CompilationError') {
        await end({
          error: true,
          id,
          status: 'Compile Error',
        });
      }

      if (
        ['SystemError', 'JudgementFailed', 'ConfigurationError'].includes(
          body.meta.status
        )
      ) {
        await end({
          error: true,
          id,
          status: 'Judgment Failed',
        });
      }

      return await end({
        id,
        status: body.meta.status,
        score: body.meta.score,
        time: body.meta.timeUsed,
        memory: body.meta.memoryUsed,
      });
    }
  }
}
