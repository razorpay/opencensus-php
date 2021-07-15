const { goToPage, changeMode } = require('../../actions/common');
const constants = require('../../const');
const { 
  replyToTicket, 
  navigateToTicketsScreen,
  addSubjectToTicket,
  selectCategories,
  closeTicketModal,
  generateScreenShot,
} = require('./actions');
const SELECTORS = require('./selectors');

describe('Tickets Page', () => {
  it('Merchants should be able to create tickets in the new UI', async() => {

    await navigateToTicketsScreen();

    await selectCategories();

    await addSubjectToTicket('Demo ticket creation');

    await expect(page).not.toHaveSelector(SELECTORS.MODAL_TRAY_CONTAINER);

    await generateScreenShot('ticket-details.png');

    await page.click(SELECTORS.SUBMIT);

    await generateScreenShot('ticket-creation-success.png');

    await page.click(SELECTORS.DONE);
  });

  it('Merchants should be able to toggle modal tray', async () => {
    await navigateToTicketsScreen();

    await generateScreenShot('ticket-categories.png');

    await selectCategories();

    await page.click(SELECTORS.SHOW);

    await expect(page).toHaveSelector(SELECTORS.MODAL_TRAY_CONTAINER);

    await page.click(SELECTORS.HIDE);

    await closeTicketModal();
  });

  it('Ticket creation should have a specific category', async() => {
    await navigateToTicketsScreen();

    const CATEGORY= { 
      category: SELECTORS.CATEGORIES.ACTIVATION,
    };

    await selectCategories(CATEGORY);

    const category = await page.$(SELECTORS.SELECTED_CATEGORY);
    expect(category.innerText).toEqual(CATEGORY.category);

    await closeTicketModal();
  });

  it('Merchants should be able to toggle subtopics', async() => {
    await navigateToTicketsScreen();

    const CATEGORY= { 
      category: SELECTORS.CATEGORIES.ACTIVATION,
      subCategory: SELECTORS.SUB_CATEGORIES['ACTIVATION'].ACTIVATION_STATUS
    };

    await selectCategories(CATEGORY);

    const subCategory = await page.$(SELECTORS.SELECTED_SUB_CATEGORY);
    expect(subCategory.innerText).toEqual(CATEGORY.subCategory);

    await page.click(SELECTORS.CHANGE_TOPIC);

    const category = await page.$(SELECTORS.SELECTED_CATEGORY);
    expect(category.innerText).toEqual(CATEGORY.category);

    await closeTicketModal();
  });

  it('Merchants should be able to change categories and subcategories', async() => {
    await navigateToTicketsScreen();

    const CATEGORY= { 
      category: SELECTORS.CATEGORIES.ACTIVATION,
    };

    await selectCategories(CATEGORY);

    const category = await page.$(SELECTORS.SELECTED_CATEGORY);
    expect(category.innerText).toEqual(CATEGORY.category);


    await page.click(SELECTORS.BACK);

    CATEGORY.category = SELECTORS.CATEGORIES.PRODUCT_INQUIRY;
    CATEGORY.subCategory = SELECTORS.SUB_CATEGORIES[CATEGORY.category].OPTIMIZER;

    const subCategory = await page.$(SELECTORS.SELECTED_SUB_CATEGORY);
    expect(subCategory.innerText).toEqual(CATEGORY.subCategory);

    await closeTicketModal();
  });
});
