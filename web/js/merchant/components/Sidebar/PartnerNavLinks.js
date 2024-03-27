import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';
import MainNavLink from 'merchant_common/components/MainNavLink';

export default function PartnerNavLinks() {
  const { isPartnerPlaybookEnabled, isPartnershipsForPosEnabled } =
    usePartnerDashboardExperiments();

  return (
    <>
      <MainNavLink
        label="Home"
        icon="i i-chart text-info"
        to="/partners"
        end
        type="partner"
        additionalCondition={(user) => user.isPartnershipFUX}
      />

      <MainNavLink
        label="Affiliate Accounts"
        icon="i i-account-balance text-success"
        to="/partners/submerchants"
        additionalCondition={(user) => user.isAllowedView('submerchants')}
        end
      />

      <MainNavLink
        label="Manage Team"
        icon="i i-settings text-warning"
        to="/partners/manage-team"
        isNew
        additionalCondition={(user) => isPartnershipsForPosEnabled && user.isAllowedTeamManagement}
        end
      />

      <MainNavLink
        label="Partner Playbook"
        icon="i i-partner-playbook text-notice"
        to="/partners/playbook"
        isNew
        additionalCondition={() => isPartnerPlaybookEnabled}
        end
      />

      <MainNavLink
        label="Earnings"
        icon="i i-earnings text-primary"
        to="/partners/earnings/daily"
        additionalCondition={(user) =>
          user.isAllowedView('earnings') && user.isHavingPartnerConfigs
        }
        end
      />

      <MainNavLink
        label="Subventions"
        icon="i i-earnings text-warning"
        to="/partners/subventions/daily"
        additionalCondition={(user) =>
          user.isAllowedView('earnings') && user.isHavingSubventionConfigs
        }
        end
      />

      <MainNavLink
        label="Settings"
        icon="i i-settings text-warning"
        to="/partners/settings"
        additionalCondition={(user) =>
          user.isAllowedView('partner_settings') && user.isPartner('aggregator', 'fully_managed')
        }
        end
      />

      <MainNavLink
        label="Applications"
        icon="i i-settings text-warning"
        to="/partners/applications"
        additionalCondition={(user) =>
          user.isAllowedView('partner_applications') && user.isPartner('pure_platform')
        }
        end
      />

      <MainNavLink
        label="Reports"
        icon="i i-books text-danger"
        to="/partners/reports"
        isPending={false}
        // disabling for reseller partner not having partner configs
        additionalCondition={(user) =>
          user.isAllowedView('partner_reports') &&
          (!user.isPartner('reseller') || user.isHavingPartnerConfigs)
        }
      />
    </>
  );
}
