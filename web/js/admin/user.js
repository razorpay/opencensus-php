import { observable } from 'mobx';

var user = observable.shallowBox(window.user);

export default user.get();
export const org = observable.shallowBox(window.org).get();
