import { openModal } from 'common/modal';

import AddCredits from '../../merchants/entity/entityModals/AddCredits';

// credit Actions
export default ({ entity, updateEntity }) => {
  function openEditModal() {
    openModal(
      <AddCredits
        model={entity}
        merchantId={entity.merchant_id}
        opts={{
          successHandler: updateEntity,
        }}
      />
    );
  }

  return (
    <button class="btn" onClick={openEditModal}>
      Edit
    </button>
  );
};
