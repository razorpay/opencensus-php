import { Link } from '@razorpay/blade/components';
import styled from 'styled-components';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

const PopoverContent = styled.p`
  font-size: ${({ theme }) => theme.typography.fonts.size[75]}px;
  line-height: ${({ theme }) => theme.typography.lineHeights[50]}px;
  padding: ${({ theme }) => `${theme.spacing[2]}px ${theme.spacing[1]}px`};
`;

const CustomerFeeBearerPopover = ({ feature }) => (
  <Popover theme="dark" align="bottom">
    <PopoverBody>
      <PopoverContent>
        {feature || 'This feature'} is not supported for merchants accepting payments as per the
        convenience fee model. To enable, click{' '}
        <Link
          href={`/app${ROUTES_INFO.CAPTURE_AND_REFUND_SETTINGS}`}
          size="small"
          htmlTitle="open capture and refund settings"
        >
          here
        </Link>{' '}
        to switch to platform fee bearer model.
      </PopoverContent>
    </PopoverBody>
  </Popover>
);
export default CustomerFeeBearerPopover;
