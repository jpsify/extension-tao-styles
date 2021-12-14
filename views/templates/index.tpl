<?php
use oat\tao\helpers\Template;
use oat\tao\model\theme\ConfigurablePlatformTheme;
?>
<link rel="stylesheet" href="<?= Template::css('styles.css', 'taoStyles') ?>"/>
<form id="tao-styles-form" name="tao-styles-form" class="content-block uploader uploaderContainer" enctype="multipart/form-data" method="post" action="<?=_url('save','Main');?>">
  <header class="section-header flex-container-full">
    <h2><?=__('Manage the Look and Feel of the TAO Platform')?></h2>
  </header>
  <section class="flex-container-remaining theme-listing-box">
    <div class="theme-listing">
        <?php foreach(get_data('themes') as $theme): ?>
          <figure data-css="<?= $theme['stylesheet'] ?>" class="theme-selection lft">
            <input
              type="radio"
              name="<?=ConfigurablePlatformTheme::ID?>"
              id="taoStyles-<?= $theme['id'] ?>"
              value="<?= $theme['id'] ?>"
              <?php if($theme['selected']):?>checked<?php endif;?>
            />
            <label for="taoStyles-<?= $theme['id'] ?>">
              <div class="svg-wrapper">
                <object data="<?= get_data('preview-svg') ?>" type="image/svg+xml">
                    <?php foreach($theme['colors'] as $key => $value):?>
                      <param name="<?=$key?>" value="<?=$value?>" />
                    <?php endforeach; ?>
                </object>
              </div>
              <span data-option="<?=ConfigurablePlatformTheme::LABEL?>"><?= $theme['label'] ?></span>
              <div class="cover"></div>
            </label>
          </figure>
        <?php endforeach; ?>
    </div>
  </section>
  <fieldset class="form-part">
    <div class="form-area">
      <h3><?=__('Upload your own logo')?></h3>
      <div class="dark-bar logo-area">
        <img class="<?=ConfigurablePlatformTheme::LOGO_URL?>" src="<?=get_data(ConfigurablePlatformTheme::LOGO_URL)?>"/>
      </div>
      <div class="panel">
        <div class="form-content">
          <div class="xhtml_form">
            <div id="upload-container" data-url="<?=_url('processLogo','Main');?>"></div>
          </div>
        </div>
      </div>
      <div class="panel">
        <label><span><?=__('Logo link')?></span></label>
        <input type="url" name="<?= ConfigurablePlatformTheme::LINK ?>" value="<?=get_data('logo' . ConfigurablePlatformTheme::LINK)?>" pattern="^http(s)?://[^<>()]+$"/>
      </div>
      <div class="panel">
        <label><span><?=__('Logo title')?></span></label>
        <input type="text" name="<?= ConfigurablePlatformTheme::MESSAGE ?>" value="<?=get_data('logo' . ConfigurablePlatformTheme::MESSAGE)?>" />
      </div>
    </div>

    <div class="form-area">
      <?php $operatedBy = get_data('operatedBy')?>
      <div class="panel">
        <h3><?=__('Add "Operated by" to the page footer')?></h3>
        <label><span><?=__('Organisation')?></span><abbr title="<?=__('Required Field')?>">*</abbr></label>
        <input type="text" required name="operatedByName" value="<?=$operatedBy['name']; ?>" />
      </div>
      <div class="panel">
        <label><span><?=__('E-mail')?></span><abbr title="<?=__('Required Field')?>">*</abbr></label>
        <input type="email" required name="operatedByEmail" value="<?=$operatedBy['email']; ?>" />
      </div>
    </div>
  </fieldset>
  <link rel="stylesheet" />
  <ul class="plain action-bar bottom-action-bar horizontal-action-bar flex-container-full">
    <li class="btn-info small rgt" role="style-saver" title="<?=__('Apply changes')?>">
      <button type="submit" class="btn-info li-inner" disabled>
        <span class="icon-save glyph"></span><?=__('Apply changes')?>
      </button>
    </li>
    <li class="btn-info small rgt" role="style-reset" title="<?=__('Discard changes')?>">
      <button type="reset" class="btn-info li-inner" disabled>
        <span class="icon-undo glyph"></span><?=__('Discard changes')?>
      </button>
    </li>
  </ul>
</form>

<?php
Template::inc('footer.tpl', 'tao');
?>
