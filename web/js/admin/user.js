import { observable } from 'mobx';

var user = observable.box(window.rzpAdmin);

export default user.get();
