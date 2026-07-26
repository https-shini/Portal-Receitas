interface Props {
  value: number;
  count?: number;
  onRate?: (score: number) => void;
}

/** Estrelas de avaliação. Somente leitura se onRate não for passado. */
export function StarRating({ value, count, onRate }: Props) {
  const rounded = Math.round(value);
  return (
    <div className="star-rating" aria-label={`Avaliação ${value.toFixed(1)} de 5`}>
      {[1, 2, 3, 4, 5].map((n) => (
        <button
          key={n}
          type="button"
          className={n <= rounded ? 'star star--on' : 'star'}
          disabled={!onRate}
          onClick={() => onRate?.(n)}
          aria-label={`${n} estrela${n > 1 ? 's' : ''}`}
        >
          ★
        </button>
      ))}
      {typeof count === 'number' && <span className="star-rating__count">({count})</span>}
    </div>
  );
}
