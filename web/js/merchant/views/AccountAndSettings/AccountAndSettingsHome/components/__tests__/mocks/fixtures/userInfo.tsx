export const defaultProps = {
  infoData: [
    {
      id: 'display_name',
      displayName: 'Display name',
      tooltip: {
        description:
          'This is the display name that you and your team will see on the Razorpay dashboard',
      },
      value: 'Dummy Name',
      isEditEnable: true,
    },
    {
      id: 'phome_number',
      displayName: 'Phone number',
      value: '9999999999',
      isEditEnable: false,
    },
    {
      id: 'login_email',
      displayName: 'Login email',
      value: 'testabc@razorpay.com',
      isEditEnable: false,
    },
    {
      id: 'password',
      displayName: 'Password',
      tooltip: {
        description:
          'This is the Password that you and your team will see on the Razorpay dashboard',
      },
      value: '*******',
      isEditEnable: true,
    },
  ],
  isMobile: true,
};
