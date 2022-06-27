import Popover, { PopoverBody } from 'common/ui/Popover';
import { titleCase } from 'common/utils/rzp-utils';

const statusMap = {
  request_rejected: 'label-danger-light',
  rejected: 'label-danger-light',
  disabled: 'label-muted',
  access_requested: 'label-primary-light',
  requested: 'label-warning',
  in_review: 'label-primary-light',
  under_review: 'label-primary-light',
  enabled: 'label-success-light',
  approved: 'label-success-light',
  activated: 'label-success-light',
  no_website_added: 'label-warning',
};

const InternationalStatusLabel = ({ status }) => {
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

  return (
    <span class={`status-label label ${statusMap[status.toLowerCase()]}`}>
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
