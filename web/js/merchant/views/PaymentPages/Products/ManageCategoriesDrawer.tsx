import React from 'react';
import { Heading, Text, Theme } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import styled from 'styled-components';

import TableBody from 'common/ui/TableBody';
import { ICategory } from 'merchant/reducers/paymentPages/types';
import {
  OpenModalType,
  ShowNotificationType,
} from 'merchant/views/PaymentPages/PaymentPages/CreateEdit/Storefront/types';
import { deleteStorefrontCategory } from 'merchant/views/PaymentPages/PaymentPages/model';
import ConfirmModal from 'merchant/views/PaymentPages/common/ConfirmModal';
import PaymentPagesDrawer from 'merchant/views/PaymentPages/common/Drawer';
import CategoryDrawer from 'merchant/views/PaymentPages/common/Products/CategoryDrawer';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

interface IManageCategoriesDrawer {
  handleClose: () => void;
  categories: ICategory[];
  onDeleteSuccess?: (string) => void;
  onEditSuccess?: (string) => void;
  openModal: OpenModalType;
  closeModal: () => void;
  showNotification: ShowNotificationType;
}

const DrawerBody = styled.div(
  ({ theme }: { theme: Theme }) => `
  margin-top: ${theme.spacing[7]}px;

  p[data-blade-component="text"] {
    display:inline;
  }

  i {
    margin-right: 3px;
  }

  td.data-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
`,
);

const ManageCategoriesDrawer = ({
  handleClose,
  categories = [],
  onDeleteSuccess,
  onEditSuccess,
  openModal,
  closeModal,
  showNotification,
}: IManageCategoriesDrawer): React.ReactElement => {
  const handleDelete = (id: string) => {
    openModal({
      isNew: true,
      component: (
        <ConfirmModal
          onAbort={closeModal}
          onAffirm={() => {
            return deleteStorefrontCategory(id)
              .then(() => {
                if (onDeleteSuccess) onDeleteSuccess(id);

                showNotification({
                  type: 'success',
                  message: 'The category was deleted successfully',
                  closeTimeout: 2500,
                });
              })
              .catch(() => {
                showNotification({
                  type: 'error',
                  message: 'Failed to delete the category. Please try again later.',
                });
              })
              .finally(() => {
                closeModal();
              });
          }}
          header="Delete category"
          message={
            <div>
              This category will be deleted from all associated payment pages. You will not be able
              to undo this. Are you sure?
              <br />
              <br />
            </div>
          }
          affirmativeLabel="Delete category"
          affirmativePendingLabel="Deleting..."
        />
      ),
    });
  };

  const handleEdit = (category: ICategory) => {
    openModal({
      isNew: true,
      component: (
        <CategoryDrawer
          categoryData={category}
          handleClose={closeModal}
          name={name}
          drawerPosition="right"
          hasTransparentBackground
          onSuccess={onEditSuccess}
          showSavedAcrossAlert
          allCategories={categories}
        />
      ),
    });
  };

  return (
    <PaymentPagesDrawer
      maskClosable={false}
      onClose={handleClose}
      hasTransparentBackground
      position="right"
    >
      <Heading weight="semibold" size="medium" color="surface.text.gray.normal">
        Manage Categories
      </Heading>
      <Text variant="body" size="medium" weight="regular" color="surface.text.gray.subtle">
        Choose from your existing products or add a new product
      </Text>
      <DrawerBody>
        <table className="table table-hover table-striped">
          <thead>
            <tr>
              <th>Category name</th>
            </tr>
          </thead>
          <TableBody rows={categories} emptyTableMsg="No categories created yet">
            {categories.map((category) => (
              <tr key={category.id}>
                <td className="data-container">
                  <div>
                    {' '}
                    <Text
                      variant="body"
                      size="medium"
                      weight="regular"
                      color="surface.text.gray.subtle"
                    >
                      {category.name}
                    </Text>
                    <Text size="small" color="surface.text.gray.muted">
                      &nbsp;({category.catalog_count} product
                      {category.catalog_count === 1 ? '' : 's'})
                    </Text>
                  </div>
                  <div>
                    <span className="btn text-primary" onClick={() => handleEdit(category)}>
                      <i className="i i-edit" /> <b>Edit</b>
                    </span>
                    <span className="btn text-danger" onClick={() => handleDelete(category.id)}>
                      <i className="i i-delete" />
                      <b>Delete</b>
                    </span>
                  </div>
                </td>
              </tr>
            ))}
          </TableBody>
        </table>
      </DrawerBody>
    </PaymentPagesDrawer>
  );
};

const mapStateToProps = () => ({});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ showNotification, closeModal, openModal }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(ManageCategoriesDrawer);
