import { validationResult } from "express-validator";
import Book from "../models/bookModel";
import { Request, Response } from "express";
import fs from "fs";
import path from "path";

const UPLOAD_DIR = path.join(__dirname, "..", "..", "uploads", "books");

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

  try {
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
      genre,
    } = req.body;

    // Validate publishedDate
    const parsedDate = new Date(publishedDate);
    if (isNaN(parsedDate.getTime())) {
      res.status(400).json({ status: 400, message: "Invalid published date" });
      return;
    }

    // Check if book with ISBN exists
    const existingBook = await Book.findOne({ isbn: isbn.trim() });
    if (existingBook) {
      res.status(400).json({ status: 400, message: "Book with this ISBN already exists" });
      return;
    }

    // Ensure upload directory exists
    if (!fs.existsSync(UPLOAD_DIR)) {
      fs.mkdirSync(UPLOAD_DIR, { recursive: true });
    }

    // Files from multer memoryStorage
    const files = req.files as { [fieldname: string]: Express.Multer.File[] } | undefined;
    const coverImage = files?.coverImage?.[0];
    const bookFile = files?.file?.[0];

    // Function to save buffer to disk with a unique filename
    const saveFile = (file: Express.Multer.File, prefix: string) => {
      const uniqueSuffix = Date.now() + "-" + Math.round(Math.random() * 1e9);
      const ext = path.extname(file.originalname);
      const filename = `${prefix}-${uniqueSuffix}${ext}`;
      const filepath = path.join(UPLOAD_DIR, filename);
      fs.writeFileSync(filepath, file.buffer);
      return `/uploads/books/${filename}`; // path to save in DB
    };

    // Save cover image file buffer to disk
    const coverImagePath = coverImage ? saveFile(coverImage, "cover") : "";

    // Save book file buffer to disk
    const bookFilePath = bookFile ? saveFile(bookFile, "file") : "";

    // Create new book document with file paths
    const newBook = new Book({
      title: title.trim(),
      genre: genre.trim(),
      author: author.trim(),
      isbn: isbn.trim(),
      publishedDate: parsedDate,
      category: category.trim(),
      language: language?.trim() || "",
      pages: pages ? Number(pages) : 0,
      publisher: publisher?.trim() || "",
      price: Number(price),
      status: status || "available",
      coverImage: coverImagePath,
      file: bookFilePath,
    });

    await newBook.save();

    res.status(201).json({
      status: 201,
      message: "Book created successfully",
      data: newBook,
    });
  } catch (error) {
    console.error("Error creating book:", error);
    res.status(500).json({ status: 500, message: "Server error" });
  }
};

export { createBook };
