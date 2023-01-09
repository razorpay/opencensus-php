/* eslint-disable prettier/prettier */
jest.mock(
  'merchant/components/SidebarV2/components/ActivationProgress',
  () => ({ onSidebarActivationClick }) => (
    <>
      <div>Activation Progress Bar</div>
      <button onClick={onSidebarActivationClick}>Click Activation</button>
    </>
  ),
);

export const state = {
  session: {
    user: {
      isAllowedView: () => true,
    },
  },
  leftNav: {
    loading: false,
    error: null,
    data: [],
  },
};
