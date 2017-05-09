//Merchant List controller
app
  .controller('InvitationsCtrl', [
    '$scope',
    '$http',
    'alertsFactory',
    'transformRequestAsFormPost',
    '$modal',
    'utils',
    function(
      $scope,
      $http,
      alertsFactory,
      transformRequestAsFormPost,
      $modal,
      utils
    ) {
      $scope.alerts = alertsFactory.getHandler();
      $scope.invitations = [];
      $scope.count = 0;

      $scope.invitation_cache = {};

      $scope.fetchInvitations = function() {
        var request = $http({
          url: '/admin/generic',

          method: 'GET',

          params: {
            route_name: 'admin_lead_get_multiple',
          },
        });

        request.success(function(data) {
          if (data.success) {
            $scope.invitations = data.data.items;
            $scope.count = data.data.count;

            data.data.items.forEach(function(v, i) {
              $scope.invitation_cache[v.id] = v;
            });
          }
        });
      };

      $scope.fetchInvitations();

      // Invitation Details

      $scope.showInvitationDetails = function(invitation_id) {
        $modal.open({
          templateUrl: 'invitationDetail.html',
          controller: 'invitationDetailCtrl',
          resolve: {
            invitation_id: function() {
              return invitation_id;
            },

            invitation_cache: function() {
              return $scope.invitation_cache;
            },
          },
        });
      };
    },
  ])
  .controller('invitationDetailCtrl', [
    '$scope',
    '$modalInstance',
    '$http',
    'invitation_id',
    'invitation_cache',
    'utils',
    function(
      $scope,
      $modalInstance,
      $http,
      invitation_id,
      invitation_cache,
      utils
    ) {
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };

      // exposing the entire service in scope
      $scope.utils = utils;

      $scope.data = invitation_cache[invitation_id].form_data;
    },
  ]);
