import React, { ReactNode, createContext, useContext, useState, useEffect } from 'react';
import { Box, Text, Heading, Badge, IconComponent } from '@razorpay/blade/components';
import { useTheme, useBreakpoint } from '@razorpay/blade/utils';

const SectionHeaderContext = createContext<{
  hasBadges: boolean;
  setHasBadges: (value: boolean) => void;
  isMobile: boolean;
}>({
  hasBadges: false,
  setHasBadges: () => { },
  isMobile: false,
});

const SectionHeader = ({ children }: { children: ReactNode }) => {
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({ breakpoints: theme.breakpoints });
  const isMobile = matchedDeviceType === 'mobile';

  const [hasBadges, setHasBadges] = useState(false);

  return (
    <SectionHeaderContext.Provider value={{ hasBadges, setHasBadges, isMobile }}>
      <Box whiteSpace='nowrap' display={{ base: hasBadges ? 'block' : 'flex', l: 'flex' }} marginBottom={isMobile ? (hasBadges ? "spacing.5" : "spacing.3") : "spacing.5"}>
        {children}
      </Box>
    </SectionHeaderContext.Provider>
  );
};

SectionHeader.Title = ({ children }: { children: string }) => {
  const { isMobile } = useContext(SectionHeaderContext);

  return (
    <>
      {isMobile ? (
        <Text variant="body" color="surface.text.gray.subtle" size="small" weight="semibold">
          {children}
        </Text>
      ) : (
        <Heading
          size="large"
          weight="semibold"
          color="surface.text.gray.normal"
          marginRight="spacing.3"
        >
          {children}
        </Heading>
      )}
    </>
  );
};

SectionHeader.Content = ({ children }: { children: ReactNode }) => {
  const { hasBadges, setHasBadges } = useContext(SectionHeaderContext);

  useEffect(() => {
    const badgesExist = React.Children.toArray(children).some(
      (child) => React.isValidElement(child) && child.type === SectionHeader.Badges,
    );
    setHasBadges(badgesExist);
  }, [children]);

  return (
    <Box display="flex" justifyContent="space-between" alignItems="center" flexGrow="1">
      {hasBadges ? (
        children
      ) : (
        <>
          <Box />
          {children}
        </>
      )}
    </Box>
  );
};

SectionHeader.Badge = ({ text, icon }: { text: string, icon: IconComponent }) => (
  <Badge icon={icon} color="neutral" size="medium" emphasis="subtle">
    {text}
  </Badge>
);

SectionHeader.Badges = ({ children }: { children: ReactNode }) => (
  <Box display="flex" gap="spacing.3">
    {children}
  </Box>
);

SectionHeader.Actions = ({ children }: { children: ReactNode }) => (
  <Box display="flex" gap="spacing.3" alignItems="center">
    {children}
  </Box>
);

export default SectionHeader;
