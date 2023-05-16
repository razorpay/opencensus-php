import Popover, { PopoverBody } from 'common/ui/Popover';
import { titleCase } from 'common/utils/rzp-utils';
import { Badge, InfoIcon } from '@razorpay/blade/components';

const statusMap = {
  request_rejected: 'label-danger-light',
  rejected: 'label-danger-light',
  deactivated: 'label-danger-light',
  disabled: 'label-muted',
  access_requested: 'label-primary-light',
  in_review: 'label-primary-light',
  under_review: 'label-primary-light',
  enabled: 'label-success-light',
  approved: 'label-success-light',
  activated: 'label-success-light',
  no_website_added: 'label-warning',
  requested: 'label-primary-light',
  action_required: 'label-action-required',
};

const badgeMapping = {
  request_rejected: 'negative',
  rejected: 'negative',
  disabled: 'neutral',
  access_requested: 'information',
  in_review: 'information',
  under_review: 'information',
  enabled: 'positive',
  approved: 'positive',
  activated: 'positive',
  requested: 'information',
  no_website_added: 'notice',
  action_required: 'notice',
};

const InternationalStatusLabel = ({ status, isIERevamp = false }) => {
  let description;
  switch (status) {
    case 'under_review':
      description = 'Usually takes 3-5 working days to review your request';
      break;
    case 'rejected':
      description = 'Please contact support for any queries';
      break;
    default:
      description = '';
      break;
  }

  return isIERevamp ? (
    !!status && (
      <Badge
        contrast="high"
        size="large"
        variant={badgeMapping[status.toLowerCase()]}
        icon={(props) => (
          <>
            <InfoIcon {...props} />
            {description && (
              <Popover theme="dark" align="bottom">
                <PopoverBody>
                  <div>{description}</div>
                </PopoverBody>
              </Popover>
            )}
          </>
        )}
      >
        {status.toUpperCase().trim()}
      </Badge>
    )
  ) : (
    <span
      class={`status-label label ${statusMap[status.toLowerCase()]}`}
      data-testid="international-status-label"
    >
      {titleCase(status)}&nbsp;
      {description && (
        <span>
          <i class="i i-info-circle" />
          <Popover theme="dark" align="bottom">
            <PopoverBody>
              <div>{description}</div>
            </PopoverBody>
          </Popover>
        </span>
      )}
    </span>
  );
};

export default InternationalStatusLabel;
