declare const __SHELL_SERVER_SENTRY_VERSION__: string;
declare const __SHELL_CLIENT_SENTRY_VERSION__: string;
declare const __SHELL_SENTRY_DSN__: string;
declare const __APP_VERSION__: string;
declare const __BUILD_MODE__: 'legacy' | 'modern';
declare const __STAGE__: string;
declare const __IS_WEBPACK_WITH_SWC__: boolean;

declare global {
  namespace Express {
    interface Request {
      context: Record<string, unknown>;
    }
  }
}
