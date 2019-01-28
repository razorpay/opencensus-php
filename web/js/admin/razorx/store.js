import { observable } from 'mobx';

export const AppStore = observable
  .box({
    appMode: 'live',

    get mode() {
      return this.appMode;
    },

    get environmentList() {
      return [
        'beta',
        'dev',
        'perf',
        'func',
        'testing',
        'stage',
        'production',
        'prod',
      ];
    },

    updateMode: function(e) {
      AppStore.appMode = e.target.value;
    },
  })
  .get();
