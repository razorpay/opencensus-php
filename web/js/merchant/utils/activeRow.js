import store from '../store';

export default item => {
  var appStore = store.getState().app;
  if (item.id === appStore.activeEntityId) {
    return 'active';
  }
  if (item.id === appStore.luminateEntityId) {
    return 'luminate';
  }
};
