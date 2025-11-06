<!DOCTYPE html>
<html>
<head>
    <title>Position Update Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Position Update Test</h1>
    <button id="testMoveTask1ToPosition0">Move Task 1 to Position 0</button>
    <button id="testMoveTask2ToPosition0">Move Task 2 to Position 0</button>
    <button id="checkPositions">Check Current Positions</button>
    
    <div id="results"></div>
    
    <script>
        // Test moving Task 1 to position 0
        $('#testMoveTask1ToPosition0').click(function() {
            $.ajax({
                url: '/taskviewer/frontend/web/index.php?r=kanban%2Fboard%2Fupdate-task-position',
                method: 'POST',
                data: {
                    taskId: 1,
                    position: 0,
                    status: 'completed',
                    _csrf: '<?php echo \Yii::$app->request->csrfToken; ?>'
                },
                success: function(response) {
                    console.log('Response:', response);
                    $('#results').html('<pre>' + JSON.stringify(response, null, 2) + '</pre>');
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    $('#results').html('<pre>Error: ' + xhr.responseText + '</pre>');
                }
            });
        });
        
        // Test moving Task 2 to position 0
        $('#testMoveTask2ToPosition0').click(function() {
            $.ajax({
                url: '/taskviewer/frontend/web/index.php?r=kanban%2Fboard%2Fupdate-task-position',
                method: 'POST',
                data: {
                    taskId: 2,
                    position: 0,
                    status: 'completed',
                    _csrf: '<?php echo \Yii::$app->request->csrfToken; ?>'
                },
                success: function(response) {
                    console.log('Response:', response);
                    $('#results').html('<pre>' + JSON.stringify(response, null, 2) + '</pre>');
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    $('#results').html('<pre>Error: ' + xhr.responseText + '</pre>');
                }
            });
        });
        
        // Check current positions
        $('#checkPositions').click(function() {
            $.ajax({
                url: '/taskviewer/frontend/web/index.php?r=kanban%2Fboard%2Findex',
                success: function(response) {
                    // This will reload the Kanban board - check if positions are correct
                    window.open('/taskviewer/frontend/web/index.php?r=kanban%2Fboard%2Findex', '_blank');
                }
            });
        });
    </script>
</body>
</html>