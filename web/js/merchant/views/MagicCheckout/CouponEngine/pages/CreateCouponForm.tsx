import React, { useEffect, useContext, useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import moment from 'moment';
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
import { globalValidator } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/helpers/createCouponFormValidators';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import {
  isCreateCouponValid,
  openCreateCouponConfirmationModal,
  createApiData,
} from 'merchant/views/MagicCheckout/CouponEngine/helpers';

// context import
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';

// type imports
import {
  CreateCouponFormProps,
  HandleCreateUpdateCouponFnProps,
} from 'merchant/views/MagicCheckout/CouponEngine/types';

const CreateCouponForm: React.FC<CreateCouponFormProps> = ({
  showNotification,
  flow = 'created',
  openModal,
  closeModal,
}) => {
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
  const [couponStatus, setCouponStatus] = useState(widgetsData.status);

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
            source: couponData?.source || null,
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
              isLimitedUsage: false,
            },
            status: 'published',
            source: null,
          }));
        }
      };

      fetchCouponData();
    }
  }, [code]);

  useEffect(() => {
    setIsFormValid(isCreateCouponValid(errorStates));
  }, [errorStates]);

  const handleReset = () => {
    resetWidgetsData();
  };

  const handleSubmit = async ({
    shouldShowConfirmationModal,
    couponStatus: updatedCouponStatus,
  }: HandleCreateUpdateCouponFnProps) => {
    setIsLoading(true);

    if (updatedCouponStatus === 'created') {
      setCouponStatus(updatedCouponStatus);
    }

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

    if (shouldShowConfirmationModal) {
      const isConfirmed = await openCreateCouponConfirmationModal(openModal, closeModal);

      if (!isConfirmed) {
        setIsLoading(false);
        return;
      }
    }

    // using form data to create api payload
    const apiData = createApiData(couponName, {
      couponDetails: widgetsData.couponDetails,
      discountDetails: widgetsData.discountDetails,
      couponValidity: widgetsData.couponValidity,
      couponEligibility: widgetsData.couponEligibility,
      usageRestriction: widgetsData.usageRestriction,
      productsPurchased: widgetsData.productsPurchased,
      combineCoupons: widgetsData.combineCoupons,
      discountOffered:
        couponName === 'bulk_order' ? widgetsData.bulkDiscountDetails : widgetsData.discountOffered,
      status: updatedCouponStatus || widgetsData.status,
      source: widgetsData.source,
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
            <button className="cancel-cta" onClick={handleReset} disabled={isLoading}>
              Cancel
            </button>
          </Link>
          {flow !== 'edit' && (
            <AsyncBtn
              className="secondary-cta"
              onClick={() =>
                handleSubmit({
                  shouldShowConfirmationModal: false,
                  couponStatus: 'created',
                })
              }
              disabled={!isFormValid || isLoading}
              isPending={isLoading && couponStatus === 'created'}
            >
              Save Coupon
            </AsyncBtn>
          )}
          <AsyncBtn
            className="primary-cta"
            onClick={() =>
              handleSubmit({
                shouldShowConfirmationModal: flow !== 'edit',
                couponStatus: widgetsData.status,
              })
            }
            disabled={!isFormValid || isLoading}
            isPending={isLoading && couponStatus === widgetsData.status}
          >
            {flow === 'edit' ? 'Update Coupon' : 'Create and Publish'}
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
      openModal,
      closeModal,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(CreateCouponForm);
