import { observable } from 'mobx';

var organization = observable.box(window.rzpOrganization);

export default organization.get();
