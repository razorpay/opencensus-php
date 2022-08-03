import Button from 'common/new-ui/Button';

export const ip = { title: 'IP Address', value: (item) => item.ip };

export const shippedOrders = { title: 'Shipped', value: (item) => item.shipped_order };

export const rtoOrders = { title: 'RTO', value: (item) => item.rto_order };

export const rtoPercent = {
  title: 'RTO %',
  value: (item) => (
    <div
      className="rto-percent"
      style={{ background: `rgba(245, 102, 102, ${(item.percentage / 100).toFixed(2)})` }}
    >
      {item.percentage.toFixed(1)}%
    </div>
  ),
};

export const action = ({ onClick }) => ({
  title: '',
  value: (item) => (
    <Button.Primary
      type="button"
      className="block-btn"
      onClick={() => onClick(item.ip, item.is_blocked)}
    >
      {item.is_blocked ? 'Unblock' : 'Block'}
    </Button.Primary>
  ),
});
