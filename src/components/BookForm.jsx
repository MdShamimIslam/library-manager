import { useState, useEffect } from 'react';
import { createBook, updateBook, fetchBook } from '../api';

const BookForm = ({ bookId, onSuccess, onCancel }) => {
  const [book, setBook] = useState({
    title: '',
    author: '',
    description: '',
    publication_year: '',
    status: 'available',
  });

  useEffect(() => {
    if (bookId) {
      fetchBook(bookId).then(data => setBook({
        title: data.title || '',
        author: data.author || '',
        description: data.description || '',
        publication_year: data.publication_year || '',
        status: data.status || 'available',
      }));
    }
  }, [bookId]);

  const handleChange = e => {
    const { name, value } = e.target;
    setBook(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (bookId) await updateBook(bookId, book);
    else await createBook(book);
    onSuccess();
  };

  return (
    <form className="lm-form" onSubmit={handleSubmit}>
      <div className="lm-form-group">
        <input name="title" placeholder="Title" value={book.title} onChange={handleChange} required />
        <input name="author" placeholder="Author" value={book.author} onChange={handleChange} />
      </div>
      <textarea name="description" rows={4} placeholder="Description" value={book.description} onChange={handleChange} />
      <div className="lm-form-group">
        <input name="publication_year" type="number" placeholder="Year" value={book.publication_year} onChange={handleChange} />
        <select name="status" value={book.status} onChange={handleChange}>
          <option value="available">Available</option>
          <option value="borrowed">Borrowed</option>
          <option value="unavailable">Unavailable</option>
        </select>
      </div>
      <div className="lm-form-actions">
        <button type="submit" className="lm-btn lm-btn-submit">{bookId ? 'Update' : 'Add'} Book</button>
        <button type="button" className="lm-btn lm-btn-cancel" onClick={onCancel}>Cancel</button>
      </div>
    </form>
  );
};

export default BookForm;
