import React, { Component } from 'react';
import { toJS } from 'mobx';
import { ModalContent } from 'component/Modal';
import Form from 'ui/Form';
import { TextAreaField, SelectField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { notifySuccess, closeModal } from 'common/modal';
import { adminFetch, adminPatch } from 'common/fetch';

export default class EditMerchantDetails extends Component {
  constructor(props) {
    super(props);
    let merchant_details = toJS(
      this.props.props.merchant.details.merchant_details
    );

    this.state = {
      isFetching: true,
      categories: {},
      data: {
        business_category: merchant_details.business_category,
        business_subcategory: merchant_details.business_subcategory,
        business_model: merchant_details.business_model,
      },
    };

    adminFetch(
      `live_${this.props.merchantId}/merchant/activation/business_categories`
    ).then(response => {
      this.setState({ isFetching: false });
      if (response) {
        this.setState({
          categories: response,
        });
      }
    });

    this.onSubmit = this.onSubmit.bind(this);
    this.handleChange = this.handleChange.bind(this);
  }

  onSubmit(body) {
    let merchantId = this.props.merchantId;
    let payload = this.state.data;

    return adminPatch({
      url: `live_${merchantId}/merchants/details`,
      data: payload,
      headers: { ['X-Razorpay-Account']: merchantId },
    }).then(data => {
      if (data) {
        notifySuccess('Merchant details updated successfully.');
        this.props.props.updateDetails({
          ...this.props.props.merchant.details,
          merchant_details: data,
        });
        closeModal();
      }
    });
  }

  handleChange(e) {
    let obj = this.state.data;

    obj[e.target.name] = e.target.value;
    if (e.target.name == 'business_category') {
      if (e.target.value == 'others') {
        obj['business_subcategory'] = null;
      } else {
        obj['business_model'] = null;
      }
    }

    this.setState({
      data: obj,
    });
  }

  render() {
    let categoryOptions, subcategoryOptions;

    if (Object.keys(this.state.categories).length) {
      categoryOptions = Object.keys(this.state.categories).map(elem => (
        <option key={elem} value={elem}>
          {this.state.categories[elem].description}
        </option>
      ));

      let subCategories = this.state.categories[
        this.state.data.business_category
      ].subcategories;
      subcategoryOptions = Object.keys(subCategories).map(elem => (
        <option key={elem} value={elem}>
          {subCategories[elem].description}
        </option>
      ));
    }

    return (
      <ModalContent header="Edit Advanced Details">
        <Form class="full-span full-elements" style={{ width: '450px' }}>
          <SelectField
            label="Business Category"
            name="business_category"
            value={this.state.data.business_category}
            disabled={this.state.isFetching}
            onChange={this.handleChange}
            required
          >
            {categoryOptions}
          </SelectField>

          {this.state.data.business_category == 'others' ? (
            <TextAreaField
              type="textarea"
              label="Business Model"
              name="business_model"
              required
              defaultValue={this.state.data.business_model}
              disabled={this.state.isFetching}
              onChange={this.handleChange}
            />
          ) : (
            <SelectField
              label="Business Subcategory"
              name="business_subcategory"
              value={this.state.data.business_subcategory}
              disabled={this.state.isFetching}
              onChange={this.handleChange}
              required
            >
              <option value=""> -- Select -- </option>
              {subcategoryOptions}
            </SelectField>
          )}

          <AsyncButton
            text="Cancel"
            class="btn btn-default"
            pendingClass="small spinner"
            onSubmit={closeModal}
          />

          <AsyncButton
            text="Save"
            class="btn"
            pendingClass="small spinner"
            onSubmit={this.onSubmit}
          />
        </Form>
      </ModalContent>
    );
  }
}
