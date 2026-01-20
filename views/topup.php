<?php
/** @var array<string,mixed> $user */
/** @var string $csrf */
?>
<div class="row g-4">
    <div class="col-12 col-lg-6">
        <div class="card card-glass rounded-4 border-0">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="icon-bubble"><i class="bi bi-wallet2"></i></div>
                    <h1 class="h4 mb-0 fw-bold">Баланс</h1>
                </div>
                <p class="text-muted mb-4">
                    Здесь отображается твой текущий баланс в биллинге.
                </p>

                <div class="p-3 rounded-4 bg-soft mb-2">
                    <div class="text-muted small">Текущий баланс</div>
                    <div class="display-6 fw-bold mb-0">
                        <?= format_rub((int)$user['balance_kopeks']) ?>
                    </div>
                </div>

                <div class="text-muted small mt-3">
                    Автоматическое пополнение через биллинг сейчас недоступно.
                    Если тебе нужно пополнить баланс, открой тикет в дискорде сервере
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card card-glass rounded-4 border-0 h-100">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="icon-bubble"><i class="bi bi-info-circle"></i></div>
                    <h2 class="h5 mb-0 fw-bold">О балансе</h2>
                </div>

                <ul class="text-muted mb-0">
                    <li>Баланс используется для оплаты игровых серверов и других услуг.</li>
                    <li>Списание происходит при создании сервера на выбранном тарифе.</li>
                    <li>Если баланс закончится, новые серверы создать не получится.</li>
                    <li>Условия возвратов и пополнения зависят от правил хостинга.</li>
                </ul>

                <hr class="border-light opacity-25 my-4">

                <div class="text-muted small">
                    Возникли вопросы по оплате или операциям с балансом?
                    Обратись в поддержку через тикеты или в дискорде сервере
                </div>
            </div>
        </div>
    </div>
</div>
