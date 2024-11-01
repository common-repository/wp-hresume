<h2 class="res_name"><?php echo $this->name(); ?></h2>
<div class="res_local"><?php echo $this->local(); ?></div>
<p class="res_summary"><?php echo $this->summary(); ?></p>

<h3>Experience</h3>

<?php
foreach($this->hresume->experience AS $exp): ?>
<div class="res_exp">
	<div class="res_exp_head">
		<h4 class="res_exp_summary"><?php echo $exp->org; ?></h4>
		<div class="res_exp_title"><?php echo $exp->title; ?></div>
	</div>
	<p class="res_exp_desc"><?php echo $exp->description; ?> </p>
	<p>
		<?php if($exp->dtstart): ?>
			<span class="res_exp_dtstart"><strong><?php if($exp->dtstart != $exp->dtend){?>Start:<?php } else { ?>Dates:<?php } ?></strong> <?php echo $this->format_date($exp->dtstart); ?></span><br />
		<?php endif; ?>
		<?php if($exp->dtend && $exp->dtstart != $exp->dtend): ?>
			<span class="res_exp_dtend"><strong>End:</strong> <?php echo $this->format_date($exp->dtend); ?></span><br />
		<?php endif; ?>
		<?php if($exp->taglist): ?>
			<span class="res_exp_skills"><strong>Skills:</strong> <?php echo $exp->taglist; ?></span>
		<?php endif; ?>
	</p>
</div>
<?php endforeach; ?>

<h3>Education</h3>

<?php foreach($this->education() AS $edu): ?>
<div class="res_edu">
	<div class="res_edu_head">
		<h4 class="res_edu_summary"><?php echo $edu->org; ?></h4>
		<div class="res_edu_title">Major: <?php echo $edu->title; ?></div>
	</div>
	<p class="res_edu_desc"><?php echo $edu->description; ?></p>
	
	<p>
		<?php if($edu->dtstart): ?>
			<span class="res_edu_dtstart"><strong>Start:</strong> <?php echo $edu->dtstart; ?></span><br />
		<?php endif; ?>
		<?php if($edu->dtend && $edu->dtstart != $edu->dtend): ?>
			<span class="res_edu_dtend"><strong>End:</strong> <?php echo $edu->dtend; ?></span><br />
		<?php endif; ?>
		<?php if($edu->taglist): ?>
			<span class="res_exp_skills"><strong>Focus:</strong> <?php echo $edu->taglist; ?></span>
		<?php endif;?>
	</p>
</div>
<?php endforeach; ?>