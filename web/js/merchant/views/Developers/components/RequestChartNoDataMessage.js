import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import Size from '@razorpay/blade-old/src/atoms/Size';

export default function NoDataMessage({ title = '' }) {
  return (
    <Size height="100%">
      <Flex justifyContent="center" alignItems="center">
        <View>
          <i className="fa fa-warning fa-lg mr-4" /> {title}
        </View>
      </Flex>
    </Size>
  );
}
