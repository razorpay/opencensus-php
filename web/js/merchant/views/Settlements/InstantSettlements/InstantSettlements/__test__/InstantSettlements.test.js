import InstantSettlement from 'merchant/models/InstantSettlement';

describe('InstantSettlement', () => {
  let ajaxCallMock;

  beforeEach(() => {
    ajaxCallMock = jest.fn().mockResolvedValue({ data: { amount: 10000 } });
    InstantSettlement.prototype.makeGenericAjaxCall = ajaxCallMock;
  });

  it('should use new API if isOdsMigrationEnabled is true', async () => {
    const instance = new InstantSettlement();
    instance.isOdsMigrationEnabled = true;
    await instance.fetch('abc123');

    expect(ajaxCallMock).toHaveBeenCalledWith({
      url: 'capital_es/service/instant_settlements/ondemand/abc123',
      data: {},
    });
  });

  it('should use old API if isOdsMigrationEnabled is false', async () => {
    const instance = new InstantSettlement();
    instance.isOdsMigrationEnabled = false;

    await instance.fetch('abc123');

    expect(ajaxCallMock).toHaveBeenCalledWith({
      url: 'settlements/ondemand/abc123',
      data: {},
    });
  });
});
