jQuery(document).on('ready',function(){
   $ = jQuery;
   
   $('#email-body').height($('#email-body').closest('td').height());
   $('#email-body').width($('#email-body').closest('td').width());
   
    $('.email-log').on('click','tr',function(){
//       alert($(this).data('emailid')); 
       
       $('.email-selected').removeClass('email-selected');
       
       $(this).addClass('email-selected');
       
       $('.email-header').empty();
       $('.email-header').append($(this).find('td').clone());
       
       $('#email-body').attr('src','emailmonitor/email.php?id='+$(this).data('emailid'));
       
       
       
    });
    
    $(window).on('resize',function(){
        $('#email-body').height($('#email-body').closest('td').height());
   $('#email-body').width($('#email-body').closest('td').width());
    });

});
