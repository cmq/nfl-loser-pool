<?php
$pickByWeek = array();
for ($i=1; $i<21; $i++) {
    $pickByWeek[$i] = 0;
}
foreach ($picks as $pick) {
    $pickByWeek[$pick['week']] = $pick['teamid'];
}
?>
<script>
loserpool.controller('PickPageCtrl', ['$scope', function($scope) {
    var pick, team;
    $scope.picks = <?php echo CJSON::encode($picks);?>;
    $scope.teams = <?php echo CJSON::encode($teams);?>;
    for (pick in $scope.picks) {
        for (team in $scope.teams) {
            if ($scope.teams[team].id == $scope.picks[pick].teamid) {
                $scope.picks[pick].team = $scope.teams[team];
                break;
            }
        }
    }
    console.dir($scope.picks);  // KDHTODO remove
}]);
</script>

<div ng-controller="PickPageCtrl">
    <table>
        <thead>
            <tr>
                <th>Week</th>
                <th>Pick</th>
                <th>Result</th>
                <th>Lock Time</th>
            </tr>
        </thead>
        <tbody>
            <?php
            for ($week=1; $week<=21; $week++) {
                ?>
                <tr>
                    <td><?=$week?></td>
                    <td>
                        <!--
                        // KDHTODO select what the user had
                        // KDHTODO add a column for the logo
                        // KDHTODO make a fancier select like the main page, to select a team by logo in a small modal?
                        // KDHTODO AJAX call to save when the dropdown changes
                        // KDHTODO disable dropdowns that are locked
                        -->
                        <select ng-model="picks[<?=$week-1?>].team" ng-options="t.longname for (id, t) in teams">
                          <option value="">Select Loser...</option>
                        </select>
                    </td>
                    <!-- KDHTODO populate -->
                    <td>Result</td>
                    <!-- KDHTODO populate -->
                    <td><?=getLockTime($week, true)?></td>
                </tr>
                <?
            }
            ?>
        </tbody>
    </table>
</div>
