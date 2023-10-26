import React, { useEffect, useContext, useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import moment from 'moment';
import isEmpty from 'lodash/isEmpty';
import { bindActionCreators, Dispatch } from 'redux';
import { connect } from 'react-redux';

// ui imports
import { CouponDetailsWidget } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon';
import {
  CreateCouponFormWrapper,
  Container,
  BackLink,
  CtaContainer,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CreateCouponFormStyles';
import { AsyncBtn } from 'common/new-ui/Button';

// constant imports
import { widgetMappings } from 'merchant/views/MagicCheckout/CouponEngine/pages/widgetMapping';

// api imports
import { createCoupon, getCoupon } from 'merchant/views/MagicCheckout/CouponEngine/api';

// helper imports
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  createCartDiscountPayload,
  createProductDiscountPayload,
  createBuyXGetYPayload,
  createBulkDiscountPayload,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/helpers/createCouponPayloads';
import { globalValidator } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/helpers/createCouponFormValidators';

// context import
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';

const CreateCouponForm: React.FC<{
  showNotification: (notification: any) => void;
  flow: string;
}> = ({ showNotification, flow = 'created' }) => {
  const { couponName, code } = useParams();
  const navigate = useNavigate();
  const {
    widgetsData,
    errorStates,
    setErrorStates,
    resetWidgetsData,
    setWidgetsData,
    allCouponsList,
  } = useContext(ModalContext);
  const [isFormValid, setIsFormValid] = useState(true);
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    if (code) {
      const fetchCouponData = async () => {
        let couponData;
        couponData = allCouponsList.find((coupon) => coupon.code === code);

        if (!couponData) {
          const { data } = await getCoupon(code);
          couponData = data.coupons[0] ?? {};
        }

        const prefillData = couponData?.meta_data?.display_information ?? {};

        if (flow === 'edit') {
          setWidgetsData((prev) => ({
            ...prev,
            ...prefillData,
            status: couponData.status,
            id: couponData.id,
          }));
        }

        if (flow === 'duplicate') {
          setWidgetsData((prev) => ({
            ...prev,
            ...prefillData,
            couponDetails: {
              ...prefillData?.couponDetails,
              code: prefillData?.couponDetails?.code
                ? `${prefillData.couponDetails.code}_copy`
                : '',
            },
            couponValidity: {
              ...prefillData?.meta_data?.display_information.couponValidity,
              startDate: moment().format('YYYY-MM-DD'),
              startTime: moment().add(2, 'hours').format('h:mm a'),
              endDate: moment().add(1, 'days').format('YYYY-MM-DD'),
              endTime: moment().add(1, 'days').endOf('day').format('h:mm a'),
              isLimitedUseage: false,
            },
            status: 'created',
          }));
        }
      };

      fetchCouponData();
    }
  }, [code]);

  useEffect(() => {
    setIsFormValid(isDeepEmpty(errorStates));
  }, [errorStates]);

  const createApiData = (couponName, data) => {
    switch (couponName) {
      case 'amount_off_order':
        return createCartDiscountPayload(data);
      case 'amount_off_products':
        return createProductDiscountPayload(data);
      case 'buyx_gety':
        return createBuyXGetYPayload(data);
      case 'bulk_order':
        return createBulkDiscountPayload(data);
      default:
        return null;
    }
  };

  const handleReset = () => {
    resetWidgetsData();
  };

  const handleSubmit = async () => {
    setIsLoading(true);

    // validate form fields
    const isFormFieldsValid = await globalValidator({
      couponName,
      widgetsData,
      setErrorStates,
      flowName: flow,
    });
    setIsFormValid(isFormFieldsValid);

    // if form fields are not valid, return
    if (!isFormFieldsValid) {
      setIsLoading(false);
      return;
    }

    // using form data to create api payload
    const apiData = createApiData(couponName, {
      couponDetails: widgetsData.couponDetails,
      discountDetails: widgetsData.discountDetails,
      couponValidity: widgetsData.couponValidity,
      couponEligibility: widgetsData.couponEligibility,
      usageRestriction: widgetsData.usageRestriction,
      productsPurchased: widgetsData.productsPurchased,
      discountOffered:
        couponName === 'bulk_order' ? widgetsData.bulkDiscountDetails : widgetsData.discountOffered,
      status: widgetsData.status,
      id: widgetsData.id,
    });

    try {
      await createCoupon(apiData);
      const successToastMessage =
        flow === 'edit' ? 'Coupon updated successfully' : 'Coupon created successfully';
      showNotification({
        type: 'success',
        message: successToastMessage,
      });
      handleReset();
      navigate('/magic/coupons');
    } catch (error) {
      showNotification({
        type: 'error',
        message: 'Something went wrong. Please try again later.',
      });
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <Container>
      <div className="width-100">
        <BackLink to="/magic/coupons" onClick={handleReset}>
          <i className="i i-chevron-left" />
          Back to All Coupons
        </BackLink>
        <CreateCouponFormWrapper>
          <CouponDetailsWidget couponName={couponName as string} flow={flow} />
          {widgetMappings[couponName as string].map((Widget, idx) => {
            return (
              <div className="widget-wrapper" key={idx}>
                <Widget couponName={couponName} flow={flow} />
              </div>
            );
          })}
        </CreateCouponFormWrapper>
        <CtaContainer>
          <Link to="/magic/coupons">
            <button className="secondary-cta" onClick={handleReset}>
              Cancel
            </button>
          </Link>
          <AsyncBtn
            className="primary-cta"
            onClick={handleSubmit}
            disabled={!isFormValid}
            isPending={isLoading}
          >
            {flow === 'edit' ? 'Update' : 'Create'} Coupon
          </AsyncBtn>
        </CtaContainer>
      </div>
    </Container>
  );
};

const mapDispatchToProps = (dispatch: Dispatch) =>
  bindActionCreators(
    {
      showNotification,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(CreateCouponForm);

function isDeepEmpty(input) {
  if (isEmpty(input)) {
    return true;
  }
  if (typeof input === 'object') {
    for (const item of Object.values(input)) {
      // if item is not undefined and is a primitive, return false
      // otherwise dig deeper
      if ((item !== undefined && typeof item !== 'object') || !isDeepEmpty(item)) {
        return false;
      }
    }
    return true;
  }
  return isEmpty(input);
}
