export const getBannerMarginTop = (isOpenedInOneDashboard: boolean, fullPageView: boolean, isMobile: boolean) => {
    if (isOpenedInOneDashboard) {
        return 'spacing.0';
    } else {
        return fullPageView ? 'spacing.0' : (isMobile ? '-8px' : '-16px');
    }
};

export const getBannerMarginHorizontal = (isOpenedInOneDashboard: boolean, fullPageView: boolean, isMobile: boolean) => {
    if (isOpenedInOneDashboard) {
        return 'spacing.0';
    } else {
        return fullPageView ? 'spacing.0' : (isMobile ? '-8px' : '-16px');
    }
};

export const getBannerPositionTop = (isOpenedInOneDashboard: boolean, fullPageView: boolean, isMobile: boolean) => {
    if (isOpenedInOneDashboard) {
        return 'spacing.0';
    } else {
        return fullPageView ? 'spacing.0' : (isMobile ? '-6px' : '-16px');
    }
};

export const getTestModeTitle = (isMobile: boolean) => {
    return isMobile ? "You are currently in test mode." : "You are currently in test mode. No real money is involved.";
};

export const getModeLinkLabel = (userActivated: boolean) => {
    return userActivated ? "Switch to Live Mode" : "Enable Live Mode";
};

export const getTestModeTooltip = (isMobile: boolean) => {
    return isMobile ? { title: "", content: "" } : { title: "Test Mode", content: "You can toggle between live and test mode from the bottom of side navigation." };
};