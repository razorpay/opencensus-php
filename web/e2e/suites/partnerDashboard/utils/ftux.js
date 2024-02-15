const hideInviteFlowFTUXBannerByClick = async ({ page, ctaLabel }) => {
  let gotItElement;

  try {
    gotItElement = await page.waitForSelector('[data-testid="invite-navlinks-ftux-gotit"]', {
      timeout: 5000,
    });
  } catch (error) {
    // Element not found within the specified timeout
  }
  if (gotItElement) {
    await gotItElement.click();
  } else {
    console.log(`${ctaLabel} Invite Flow FTUX "GOT IT" element not found.`);
  }
};

export const hideInviteFlowFTUXBannersIfPresent = async ({ page }) => {
  const ctaList = ['ACCEPTED_INVITES', 'ALL_INVITES'];
  await hideInviteFlowFTUXBannerByClick({ page, ctaLabel: ctaList[0] });
  await hideInviteFlowFTUXBannerByClick({ page, ctaLabel: ctaList[1] });
};
