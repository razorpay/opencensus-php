import { Sections } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/config/section';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import {
  AdditionalContextInterface,
  SectionCardInterface,
  SubSection,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';

const paymentMethodSection = {
  isSubSectionEnabled: ({ instruments, product }): SubSection | null =>
    instruments.find((each) => each.slug === product.id),
};

const sectionUtil = {
  payment_methods: {
    subSection: {
      ...paymentMethodSection,
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

export const getSectionCards = ({
  user,
  instruments,
  mode,
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
                  !subSection[subSectionUtil]({ instruments, product: eachSubSections }),
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
  return SectionCards;
};
