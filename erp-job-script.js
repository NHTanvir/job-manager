jQuery(document).ready(function ($) {
    $('.erp-job-submit-btn').on('click', function (e) {
        e.preventDefault();

        var fd              = new FormData();
        var file            = jQuery(document).find('input[type="file"]');
        var caption         = jQuery(this).find('input[name=img_caption]');
        var individual_file = file[0].files[0];
        fd.append("file", individual_file);
        var individual_capt = caption.val();
        fd.append("caption", individual_capt);  
        fd.append('action', 'epr_job_submit');  

        jQuery.ajax({
            type: 'POST',
            url: ERPJOB.ajaxurl,
            data: fd,
            contentType: false,
            processData: false,
            success: function(response){

                console.log(response);
            }
        });
    });

});


