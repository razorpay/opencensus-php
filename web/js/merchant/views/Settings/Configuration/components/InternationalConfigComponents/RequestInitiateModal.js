import React, { useState } from 'react';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import ModalHeader from 'common/ui/ModalHeader';
import { merchantFetch } from 'merchant/utils/ajax';

const RequestInitiateModal = ({
  closeModal,
  openTypeForm,
  triggerSource = '',
  showNotification,
}) => {
  const [products, setProducts] = useState({ pg: 1, otherProducts: 0 });

  const togglePG = (e) => {
    setProducts({ ...products, pg: Number(e.target.value) });
  };

  const toggleOtherProducts = (e) => {
    setProducts({ ...products, otherProducts: Number(e.target.value) });
  };

  const sendSelectedProducts = async () => {
    let selectedProducts = [];

    if (triggerSource === 'pg') {
      selectedProducts.push('payment_gateway');
    } else if (triggerSource === 'otherProducts') {
      selectedProducts = selectedProducts.concat(['payment_links', 'payment_pages', 'invoices']);
    } else {
      if (products.pg) {
        selectedProducts.push('payment_gateway');
      }

      if (products.otherProducts) {
        selectedProducts = selectedProducts.concat(['payment_links', 'payment_pages', 'invoices']);
      }
    }

    try {
      const res = await merchantFetch({
        url: 'merchant/international/product',
        method: 'patch',
        headers: {
          'content-type': 'application/json',
        },
        data: JSON.stringify({
          products: selectedProducts,
        }),
      });
      return res;
    } catch (err) {
      showNotification({
        type: 'error',
        message: err.errors[0],
      });
      return null;
    }
  };

  const onProvideDetailsClick = async () => {
    const res = await sendSelectedProducts();
    if (res && res.success) {
      closeModal();
      openTypeForm(triggerSource);
    } else {
      closeModal();
    }
  };

  const isProvideDetailsDisabled = () => {
    if (triggerSource === 'pg' || triggerSource === 'otherProducts') {
      return false;
    }

    if (products.pg || products.otherProducts) {
      return false;
    }

    return true;
  };

  const getDescriptionContent = () => {
    if (triggerSource === 'pg') {
      return (
        <div>
          To enable international payments for Payment Gateway, we need some details about your
          business.
        </div>
      );
    }

    if (triggerSource === 'otherProducts') {
      return (
        <div>
          To enable international payments for Payment Pages, Payment Links & Invoices, we need some
          details about your business.
        </div>
      );
    }

    return (
      <div>
        We would need some details about your business. How would you like to accept international
        payments?
        <Input.Check
          fieldLabel="On Payment Gateway"
          onChange={togglePG}
          defaultValue={products.pg}
        />
        <Input.Check
          fieldLabel="On Other Products"
          description="Payment Pages, Payment Links & Invoices"
          onChange={toggleOtherProducts}
          defaultValue={products.otherProducts}
        />
      </div>
    );
  };

  return (
    <div className="Modal__Request-initiate-modal">
      <ModalHeader title="Enable International Payments" onCloseClick={closeModal} />
      <div className="modal-body">
        {getDescriptionContent()}
        <div className="Modal__actions text-right">
          <Button.Primary onClick={onProvideDetailsClick} disabled={isProvideDetailsDisabled()}>
            Provide Details
          </Button.Primary>
        </div>
      </div>
    </div>
  );
};

export default RequestInitiateModal;
