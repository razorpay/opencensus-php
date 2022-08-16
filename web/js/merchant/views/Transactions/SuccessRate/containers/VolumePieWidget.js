import React from 'react';
import { connect } from 'react-redux';
import { Pie } from 'react-chartjs-2';
import { isEmpty, compact, capitalize } from 'lodash';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import Spinner from 'common/ui/Spinner';
import StyledHeader from '../components/StyledHeader';
import NoDataMessage from '../components/NoDataMessage';
import { pieChartOptions as options, piePlugins as plugins } from '../chartConfig';
import { getPieChartData } from '../helper';

const renderInfoCard = ({ name, successful, total, sr } = {}) => {
  if (!sr) return null;
  return (
    <div className="info-card">
      <StyledHeader text={capitalize(name) ?? '--'} />
      <div className="info-card__label">
        <p className="label-text">Successful / Total Attempts</p>
      </div>
      <div className="info-card__value">
        <p>
          <span className="highlight">{successful}</span>/{total} ({sr}%)
        </p>
      </div>
    </div>
  );
};

const VolumePieWidget = (props) => {
  const { isLoading, tab = {} } = props;
  const { group_by, data } = tab;
  const groupData = data?.groups?.[group_by];
  const pieChartData = getPieChartData(groupData);
  const compactData = compact(pieChartData?.datasets?.[0]?.data);

  if (isLoading) {
    return (
      <div className="box-widget volume-container">
        <Spinner />
      </div>
    );
  }

  return (
    <div className="box-widget volume-container">
      <div className="chart-col">
        <StyledHeader text="Volume of attempts" />
        {!isEmpty(compactData) ? (
          <div className="pie-chart">
            <Pie data={pieChartData} options={options} plugins={plugins} />
          </div>
        ) : (
          <NoDataMessage title="No data available." />
        )}
      </div>
      <div className="info-col">
        {!isLoading && data?.sr ? (
          <div className="info-card">
            {isLoading ? (
              <PlaceholderLoader style={{ marginBottom: '10px' }} />
            ) : (
              <StyledHeader text="Overall" />
            )}
            {isLoading ? (
              <PlaceholderLoader />
            ) : (
              <div className="info-card__label">
                <p className="label-text">Successful / Total Attempts</p>
              </div>
            )}
            {isLoading ? (
              <PlaceholderLoader />
            ) : (
              <div className="info-card__value">
                <p>
                  <span className="highlight">{data?.successful}</span>/{data?.total} ({data?.sr}
                  %)
                </p>
              </div>
            )}
          </div>
        ) : undefined}
        {!isEmpty(groupData) && groupData?.map(renderInfoCard)}
      </div>
    </div>
  );
};

const mapStateToProps = ({ successRate }) => {
  const { tabLoading, activeTab, tabs } = successRate;
  return {
    activeTab,
    isLoading: tabLoading,
    tab: tabs[activeTab],
  };
};

export default connect(mapStateToProps, null)(VolumePieWidget);
