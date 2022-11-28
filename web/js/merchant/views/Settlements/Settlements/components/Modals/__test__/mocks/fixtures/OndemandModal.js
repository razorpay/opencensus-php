export const defaultProps = {
  animatedSettlementBtn: true,
  settlableAmount: false,
  currentBalance: 990000, // balance
  fromWhere: 'Settlements',
  eventCategory: 'Dashboard - Early Settlement',
  checkIfFirstEverSettlement: jest.fn().mockReturnValue(true),
  goBackToInitialModalView: jest.fn(),
};

export const user = {
  isOndemandSettlementEnabled: true,
  isOndemandSettlementsRestricted: false,
};

const keyCodes = {
  Escape: 27,
};

export function patchKeyEvent(e) {
  Object.defineProperty(e, 'keyCode', {
    get: () => keyCodes[e.code] ?? 0,
  });
}
