/**
 * Created by setler on 15.6.16.
 */
(function($){
    $(function(){
        var progressbar = $( "#progressbar" ),
            progressLabel = $( ".progress-label" );

        progressbar.progressbar({
            value: false,
            max: typeof all != 'undefined' ? all : null,
            change: function() {
                progressLabel.text( progressbar.progressbar( "value" ) + " processed" );
            },
            complete: function() {
                progressLabel.text( "Complete!" );
                location.reload();
            }
        });

        function batch(start, count) {

            $.ajax({
                url: url,
                method: 'POST',
                dataType: 'json',
                data: {request: {start: start, count: count, path_to_file: path_to_file}},
                success: function(data, textStatus, jqXHR) {

                    if(data.response !== 'undefined' && !data.response.stop) {

                        var val = progressbar.progressbar( "value" ) || 0;

                        if(parseInt(data.response.count) + start > 0) {
                            progressbar.progressbar( "value", parseInt(data.response.count) + start);
                        } else {
                            progressLabel.text( "Complete!" );
                        }

                        batch(parseInt(data.response.count) + start, count);
                    } else {
                        progressbar.progressbar( "value", "Error!!!" );
                    }
                }
            });
        }

        if(typeof start != 'undefined'
            && typeof count != 'undefined'
            && typeof path_to_file != 'undefined') {

            progressbar.show();
            batch(start, count);
        }

    });
})(jQuery);