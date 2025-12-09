const ConfirmDelete = ({ onConfirm, onCancel }) => {
  return (
    <div className="lm-confirm">
      <p>Are you sure you want to delete this book?</p>
      <div>
        <button className="lm-btn lm-btn-confirm" onClick={onConfirm}>Yes, delete</button>
        <button className="lm-btn lm-btn-cancel" onClick={onCancel}>Cancel</button>
      </div>
    </div>
  );
};

export default ConfirmDelete;
