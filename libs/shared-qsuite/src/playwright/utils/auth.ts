
export const loginByMobile = async ({ page, mobile }: any) => {
    await page.click('input[type="text"]');
    await page.fill('input[type="text"]', mobile);
    await page.route('**/user/signin/otp', async (route: any, request: any) => {
      console.log('SMS mock request intercepted');
      if (request.postData) {
        const existingBody = await request.postData();
        const requestBody = JSON.parse(existingBody);
  
        // add sms mock flag
        requestBody.skip_sms_request = true;
  
        const newRequestBody = JSON.stringify(requestBody);
  
        route.continue({ postData: newRequestBody });
      } else {
        route.continue();
      }
    });
    await page.click('text="Next"');
    await page.click('input[id="Enter OTP"]');
    await page.fill('input[id="Enter OTP"]', '000007');
    await Promise.all([page.waitForNavigation(), page.click('text="Login"')]);
  };
  
  export const loginByEmail = async ({ page, cred }: any) => {
    await page.click('input[type="text"]');
    console.log('cred.username', cred.username);
    await page.fill('input[type="text"]', cred.username);
    await page.click('text="Next"');
    await page.click('input[type="password"]');
    await page.fill('input[type="password"]', cred.password);
    await Promise.all([page.waitForNavigation(), page.click('text="Login"')]);
  };
  
  
  
  
  