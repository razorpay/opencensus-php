import { routes, test, expect, getStorageStatePath } from '@libs/shared-qsuite/playwright';

test.describe('Test GCMS Programs @flow=programs @project=payments ', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.GCMS_PROGRAMS);
    await expect(page).toHaveURL(routes.GCMS_PROGRAMS);
  });

  test.skip('should be able view gcms programs page', async ({ page }) => {
    await expect(await page.getByText('Gift Card Programs').first()).toBeVisible();
    await expect(await page.getByText('rows / page').first()).toBeVisible();
  });

  test.skip('should be able to navigate to program details page', async ({ page }) => {
    // Simumlating user click on the first program card 
    const firstCard = await page.locator('[data-blade-component="card-body"]')
    await expect(firstCard).toBeVisible();
    var box = await firstCard.boundingBox()
    await page.mouse.click(box.x + box.width/2, box.y + box.height/2)

    // Tabs to be visible
    await expect(await page.getByText('Details').first()).toBeVisible();
    await expect(await page.getByText('Resellers').first()).toBeVisible();

    // Program Details section to be visible
    await expect(await page.getByText('Program Details')).toBeVisible();
    // Gift Card Details section to be visible
    await expect(await page.getByText('Gift Card Details')).toBeVisible();
  });

  test('should be able to navigate to reseller mapping tab', async ({ page }) => {
   
    // Simumlating user click on the first program card 
    const firstCard = await page.locator('[data-blade-component="card-body"]')
    await expect(firstCard).toBeVisible();
    var box = await firstCard.boundingBox()
    await page.mouse.click(box.x + box.width/2, box.y + box.height/2)

    // Tabs to be visible
    await expect(await page.getByText('Details').first()).toBeVisible();
    await expect(await page.getByText('Resellers').first()).toBeVisible();
    
    const resellersTab = await page.locator("text=Resellers").locator("..");
    // const resellersTab = await page.getByText("Resellers").first()
    await resellersTab.click();

    await expect(await page.getByText("No reseller added")).toBeVisible()
 

  });

});
