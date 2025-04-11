import { Sections } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/config/section';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import {
  AdditionalContextInterface,
  SectionCardInterface,
  SubSection,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import {
  BusinessSettingsFields,
  SectionCardDataFields,
  WebsiteAppSettingsFields,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings/section';

const paymentMethodSection = {
  isSubSectionEnabled: ({ instruments, product }): SubSection | null =>
    instruments.find((each) => each.slug === product.id),
};

const applicationsSubSection = {
  isSubSectionEnabled: ({ shouldShowApplications, product }) => {
    if (product.id !== WebsiteAppSettingsFields.APPLICATIONS) return true;
    return shouldShowApplications;
  },
};

const sectionUtil = {
  [SectionCardDataFields.PAYMENT_METHODS]: {
    subSection: {
      ...paymentMethodSection,
    },
  },
  [SectionCardDataFields.WEBSITE_APP_SETTINGS]: {
    subSection: {
      ...applicationsSubSection,
    },
  },
};

const showWhen = ({ additionalCondition, mode, ...rest }): boolean => {
  return (
    (additionalCondition &&
      showWhenUtil({ additionalCondition: additionalCondition({ mode, ...rest }) })) ||
    !additionalCondition
  );
};

const getBillMeSectionCards = (sectionCards) => {
  const billMeSectionCards = sectionCards.find(
    (section) => section.id === SectionCardDataFields.BUSINESS_SETTINGS,
  );
  if (billMeSectionCards) {
    billMeSectionCards.subSections = billMeSectionCards.subSections.filter(
      (subSection) =>
        subSection.id === BusinessSettingsFields.BILLME_SETTINGS ||
        subSection.id === BusinessSettingsFields.STORE_SETTINGS,
    );
 
    if (billMeSectionCards.subSections.length > 0) {
      return [billMeSectionCards];
    }   
  }
  return [];
};

export const getSectionCards = ({
  user,
  instruments,
  shouldShowApplications,
  mode,
  isBillmeMerchant,
  ...rest
}: AdditionalContextInterface): SectionCardInterface[] => {
  const SectionCards = Sections.reduce((accumulator, eachSection) => {
    const { id, subSections, additionalCondition } = eachSection;
    if (showWhen({ additionalCondition, mode, ...rest })) {
      const { section, subSection } = sectionUtil[id] || {};
      const isSectionUtilDisabled =
        section &&
        Object.keys(section).find(
          (sectionUtils) => !section[sectionUtils]({ user, instruments, mode }),
        );
      if (!isSectionUtilDisabled) {
        const validSections = subSections.filter((eachSubSections: SubSection): boolean => {
          const { additionalCondition } = eachSubSections;
          if (showWhen({ additionalCondition, mode, ...rest })) {
            const isSubSectionUtilDisabled =
              subSection &&
              Object.keys(subSection).find(
                (subSectionUtil) =>
                  !subSection[subSectionUtil]({
                    instruments,
                    product: eachSubSections,
                    shouldShowApplications,
                  }),
              );
            if (!isSubSectionUtilDisabled) {
              return true;
            }
          }
          return false;
        });
        if (validSections?.length) {
          accumulator.push({ ...eachSection, subSections: validSections });
        }
      }
    }
    return accumulator;
  }, [] as SectionCardInterface[]);
  if (isBillmeMerchant) {
    return getBillMeSectionCards(SectionCards);
  }
  return SectionCards;
};
