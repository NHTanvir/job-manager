jQuery(document).ready(function ($) {
    $(document).on('click', '.erp-job-submit-btn', function(e){
        e.preventDefault();

        var cxc_form            = new FormData();
        var file                = $(document).find('input[type="file"]');
        var cxc_individual_file = file[0].files[0];
        let fullName            = $('#full_name').val();
        let email               = $('#email').val();
        let message             = $('#message').val();
        let post_id             = $('#post_id').val();

        if(fullName === '' || email === '' || message === '' || cxc_individual_file == undefined ){
            alert('Please fill in all the required fields and upload CV.');
        }
        else{
            cxc_form.append('full_name', fullName);
            cxc_form.append('email', email);
            cxc_form.append('message', message);
            cxc_form.append('post_id', post_id);
            cxc_form.append("file", cxc_individual_file);
            cxc_form.append('action', 'cxc_upload_file_data');

            $.ajax({
                type: 'POST',
                url: ERPJOB.ajax_url,
                data: cxc_form,
                contentType: false,
                processData: false,
                success: function( cxc_response ){

                    if( cxc_response != '' && cxc_response.success ){
                        $('.cxc_upload_form')[0].reset();
                        $('.erp-job-modal p').text(cxc_response.message);
                        $('.erp-job-modal').show();
                    }
                    else{
                        $('.erp-job-modal p').text( cxc_response.message );
                        $('.erp-job-modal').show();
                    }
                    
                }
            });
        }
    });

    $(document).on('click', '#erp-job-status', function(e){
        e.preventDefault();
        var selectedStatus  = $(this).closest('tr').find('select[name="status"]').val();
        var id              = $(this).closest('tr').find('td:nth-child(1)').data('id');

        $.ajax({
            url: ERPJOB.ajax_url,
            type: 'POST',
            data: {
                action: 'update_status',
                status: selectedStatus,
                id: id
            },
            success: function(response) {
                $('.erp-job-modal p').text(response.message);
                $('.erp-job-modal').show();
            }
        });
    });

    $(document).on('click', '.erp-job-modal-close', function(e) {
        $('.erp-job-modal').hide();
    });
});


