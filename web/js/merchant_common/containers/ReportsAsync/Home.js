import { NavLink } from 'react-router-dom';
import TestModeBanner from 'merchant/containers/TestModeBanner';

import LogList from './Logs/List';

export default class ReportHome extends React.PureComponent {
  static defaultProps = {
    logs: [
      {
        id: 1,
        name: 'Payment Report',
        format: 'csv',
        created_at: 1574829610,
        start_time: 1574775699,
        end_time: 1574775699,
        status: 'processed',
        fileId: null,
        template_overrides: {
          file_meta: {
            extension: 'csv',
          },
        },
      },
    ],
  };

  render() {
    return (
      <div>
        <tabbed-container>
          <header>
            <NavLink to="/reports">Reports</NavLink>
          </header>
          <TestModeBanner />
          <content>
            <div class="content-wrapper">
              <LogList logs={this.props.logs} />
            </div>
          </content>
        </tabbed-container>
      </div>
    );
  }
}
