<?php

use App\Application\Support\Slug;

/**
 * Card de receita reutilizável (catálogo, relacionadas, favoritas).
 * O card inteiro é um link para a página dedicada — não abre mais modal.
 *
 * @var array    $card       ['id','name','image','time','category', 'difficulty'?]
 * @var int|null $cardIndex  Posição na grade (para o atraso da animação).
 * @var bool     $cardEager  true carrega a imagem sem lazy (primeiras dobras).
 */
$slug = Slug::make((string) $card['name']);
$delay = isset($cardIndex) ? min((int) $cardIndex * 40, 400) : 0;
$loading = !empty($cardEager) ? 'eager' : 'lazy';
?>
<li class="card recipe-card reveal" style="animation-delay: <?= $delay ?>ms">
    <a class="recipe-card__btn" href="receita/<?= (int) $card['id'] ?>/<?= $slug ?>"
       aria-label="Ver receita: <?= htmlspecialchars((string) $card['name']) ?>">
        <span class="recipe-card__media">
            <img src="./assets/img/<?= htmlspecialchars((string) $card['image']) ?>"
                 alt="" loading="<?= $loading ?>" width="400" height="300">
        </span>
        <span class="recipe-card__body">
            <span class="recipe-card__title"><?= htmlspecialchars((string) $card['name']) ?></span>
            <span class="recipe-card__meta">
                <span><i class="las la-clock" aria-hidden="true"></i><?= htmlspecialchars((string) $card['time']) ?></span>
                <span><i class="las la-tag" aria-hidden="true"></i><?= htmlspecialchars((string) $card['category']) ?></span>
                <?php if (!empty($card['difficulty'])): ?>
                    <span><i class="las la-signal" aria-hidden="true"></i><?= htmlspecialchars((string) $card['difficulty']) ?></span>
                <?php endif; ?>
                <?php if (!empty($card['rating']['count'])): ?>
                    <span class="recipe-card__rating"><i class="las la-star" aria-hidden="true"></i><?= htmlspecialchars(number_format((float) $card['rating']['average'], 1, ',', '')) ?> <span class="recipe-card__rating-count">(<?= (int) $card['rating']['count'] ?>)</span></span>
                <?php endif; ?>
            </span>
        </span>
    </a>
</li>
<?php unset($slug, $delay, $loading); ?>
