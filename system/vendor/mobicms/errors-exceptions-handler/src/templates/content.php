<div class="stackTrace">
    <?php if (!empty($this->message)): ?>
        <h2>Stack Trace</h2>
    <?php endif ?>

    <div class="trace">
        <div class="info">
            <div class="counter"><?= $this->counter ?></div>
            <div class="fileinfo"><?= $this->file ?>, line <?= $this->line ?></div>
        </div>
        <?= $this->code ?>
    </div>
</div>
