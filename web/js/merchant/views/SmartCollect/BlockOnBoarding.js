import ErrorImage from 'assets/error.svg';

const BlockOnBoarding = () => {
  return (
    <div class="SmartCollect--Block">
      <div class="info">
        <div>
          <div class="heading">Smart Collect</div>
          <div class="divider" />
          <div class="desc">This feature is not supported for your business type.</div>
        </div>
      </div>

      <div class="error-img">
        <img src={ErrorImage} />
      </div>
    </div>
  );
};

export default BlockOnBoarding;
