import React from 'react';
import { connect } from 'react-redux';

import User from 'common/typings/User';
import IntoViewUsingQueryParams from 'common/ui/IntoViewUsingQueryParams';
import Paypal from 'merchant/views/Settings/PaymentMethods/components/Paypal';

import MethodSection from './Section';
import { MethodProps } from './types';

const MethodWallet = ({
  item,
  user,
}: MethodProps & {
  user: User;
}) => {
  if (!item) {
    return null;
  }

  const listItem = item.list?.[0];

  return (
    <MethodSection id={item.slug || 'wallets'}>
      <IntoViewUsingQueryParams queryKey="instrument" queryValue="paypal" key={listItem?.slug}>
        <Paypal instrument={listItem} user={user} isIERevamp />
      </IntoViewUsingQueryParams>
    </MethodSection>
  );
};

const mapStateToProps = ({ session }) => ({
  user: session.user,
});

export default connect(mapStateToProps)(MethodWallet);
