import Banner from 'common/ui/Banner';

export default ({ ctaOnClick }) => {
  return (
    <div class="TestModeBanner">
      <Banner>
        Your settlements are not being processed. They have been put on hold.{' '}
        <span class="btn-link">View Details</span>
      </Banner>
    </div>
  );
};
