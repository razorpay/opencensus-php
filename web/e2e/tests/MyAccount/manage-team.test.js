const { goToPage } = require('../../actions/common');
const { createFakeEmail } = require('../../utils');
const constants = require('../../const');
const { addMember } = require('./actions');

describe('Manage Team', () => {
  it('Merchant should be able to invite member', async () => {
    await goToPage(page, constants.routes.MY_ACCOUNT);

    const newMemberEmail = createFakeEmail();
    await addMember(page, newMemberEmail);
    await expect(page).toHaveSelector(`css=td >> text=${newMemberEmail}`);
  });
});
