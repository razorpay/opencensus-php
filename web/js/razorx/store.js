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
        'automation',
        'bvt',
        'axis',
        'testing',
        'stage',
        'production',
        'prod',
      ];
    },

    updateMode: function (e) {
      let newMode = e;
      if (!newMode) {
        return;
      }

      if (typeof newMode.target !== 'undefined') {
        newMode = newMode.target.value;
      }

      AppStore.appMode = newMode;
    },
  })
  .get();
