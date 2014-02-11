<script>
loserpool.controller('PickPageCtrl', ['$scope', function($scope) {
    $scope.picks        = <?php echo CJSON::encode($picks);?>;
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
                        <select week="<?=$week?>">
                            <option value="0">Select Loser...</option>
                        </select>
                    </td>
                    <td>Result</td>
                    <td>Lock Time</td>
                </tr>
                <?
            }
            ?>
        </tbody>
    </table>
</div>
