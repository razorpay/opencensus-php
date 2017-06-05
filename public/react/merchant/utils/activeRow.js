import store from '../store';

export default item =>
  (item.id === store.getState().app.activeEntityId ? 'active' : '');
