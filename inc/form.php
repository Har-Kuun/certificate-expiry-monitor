<div id='sslform'>
  <form class="form-horizontal" action="add.php" method="POST">
    <p>请输入您想进行证书过期检测的域名，最多20个。<br></p>
    <fieldset>

      <div class="form-group">
        <label class="col-md-1 control-label" for="domains">Domains</label>
        <div class="col-md-5">                     
          <textarea class="form-control" required="true"  rows=6 id="domains" name="domains" placeholder="example.org"></textarea>
        </div>
      </div>

      <div class="form-group">
        <label class="col-md-1 control-label" for="email">Email</label>  
        <div class="col-md-5">
          <input id="email" name="email" required="true" type="email" placeholder="请输入您的邮箱" class="form-control input-md" >
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-4">
          <label class="col-md-2 col-md-offset-1 control-label" for="s"></label>
          <button id="s" name="s" class="btn btn-primary">提交</button>
        </div>
      </div>
    </fieldset>
  </form>
</div>

