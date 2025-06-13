import { validationResult } from "express-validator";
import Book from "../models/bookModel"; // Assuming this is the correct path
import { Request, Response } from "express";

const createBook = async (req: Request, res: Response): Promise<void> => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) {
    res.status(422).json({
      status: 422,
      message: "Validation failed",
      errors: errors.array(),
    });
    return;
  }

  // Destructure the book fields from the request body
  const {
    title,
    author,
    isbn,
    publishedDate,
    category,
    language,
    pages,
    publisher,
    price,
    status,
  } = req.body;

  try {
    // Check if a book with the same ISBN already exists (assuming ISBN is unique)
    const existingBook = await Book.findOne({ isbn: isbn.trim() });
    if (existingBook) {
      res.status(400).json({
        status: 400,
        message: "Book with this ISBN already exists",
      });
      return;
    }

    // Create a new Book document
    const newBook = new Book({
      title: title.trim(),
      author: author.trim(),
      isbn: isbn.trim(),
      publishedDate: new Date(publishedDate),
      category: category.trim(),
      language: language ? language.trim() : "",
      pages: pages ? Number(pages) : 0,
      publisher: publisher ? publisher.trim() : "",
      price: Number(price),
      status: status || "available",
    });

    // Save the new book
    await newBook.save();

    res.status(201).json({
      status: 201,
      message: "Book created successfully",
      data: newBook,
    });
  } catch (error) {
    console.error("Error creating book:", error);
    res.status(500).json({
      status: 500,
      message: "Server error",
    });
  }
};

export { createBook };
