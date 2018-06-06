import { merchantFetch } from 'rzp/utils/ajax';

/* Specific Api Actions of Reusable Payment Links */
export function RPLCreate() {
  const curDirtyForm = this.state.dirty[this.state.activeTab];
  console.log('RPL', curDirtyForm);
}
