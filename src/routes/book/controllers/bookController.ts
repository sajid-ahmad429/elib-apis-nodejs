/* eslint-disable @typescript-eslint/no-explicit-any */
import { validationResult } from "express-validator";
import { Request, Response } from "express";
import Book from "../models/bookModel";
import cloudinary from "../../../config/cloudinary";
import streamifier from "streamifier";

const allowedImageMimeTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
const allowedFileMimeTypes = ["application/pdf"];

/**
 * Uploads buffer data to Cloudinary.
 */
const uploadToCloudinary = (
  file: Express.Multer.File,
  folder: string
): Promise<string> => {
  return new Promise((resolve, reject) => {
    const isPDF = file.mimetype === "application/pdf";

    const uploadStream = cloudinary.uploader.upload_stream(
      {
        folder,
        resource_type: isPDF ? "raw" : "image",
        use_filename: true,
        unique_filename: false,
        filename_override: file.originalname,
      },
      (error, result) => {
        if (error || !result) {
          console.error("Cloudinary upload error:", error);
          return reject(new Error("Cloudinary upload failed"));
        }

        let fileUrl = result.secure_url;
        if (isPDF && !fileUrl.endsWith(".pdf")) {
          fileUrl += ".pdf";
        }

        resolve(fileUrl);
      }
    );

    streamifier.createReadStream(file.buffer).pipe(uploadStream);
  });
};

/**
 * Main controller to create a book.
 */
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

    // Validate published date
    const parsedDate = new Date(publishedDate);
    if (isNaN(parsedDate.getTime())) {
      res.status(400).json({ status: 400, message: "Invalid published date" });
      return;
    }

    // Check if ISBN already exists
    const existingBook = await Book.findOne({ isbn: isbn.trim() });
    if (existingBook) {
      res.status(400).json({ status: 400, message: "Book with this ISBN already exists" });
      return;
    }

    // Ensure multer files exist
    const files = req.files as { [fieldname: string]: Express.Multer.File[] } | undefined;
    if (!files) {
      res.status(400).json({ status: 400, message: "No files uploaded" });
      return;
    }

    const coverImage = files.coverImage?.[0];
    const bookFile = files.file?.[0];

    // Validate cover image
    if (!coverImage || !allowedImageMimeTypes.includes(coverImage.mimetype)) {
      res.status(400).json({
        status: 400,
        message: "Invalid or missing cover image. Allowed formats: JPEG, PNG, GIF, WEBP",
      });
      return;
    }

    // Validate book file
    if (!bookFile || !allowedFileMimeTypes.includes(bookFile.mimetype)) {
      res.status(400).json({
        status: 400,
        message: "Invalid or missing book file. Only PDF files are allowed",
      });
      return;
    }

    // Upload files to Cloudinary
    const coverImageUrl = await uploadToCloudinary(coverImage, "books/coverImages");
    const bookFileUrl = await uploadToCloudinary(bookFile, "books/files");
    
    // Save book in DB
    const newBook = new Book({
      title: title?.trim(),
      genre: genre?.trim() || "",
      author: req.userId,
      isbn: isbn?.trim(),
      publishedDate: parsedDate,
      category: category?.trim(),
      language: language?.trim() || "",
      pages: pages ? Number(pages) : 0,
      publisher: publisher?.trim() || "",
      price: Number(price),
      status: status || "available",
      coverImage: coverImageUrl,
      file: bookFileUrl,
    });

    await newBook.save();

    res.status(201).json({
      status: 201,
      message: "Book created successfully",
      data: newBook,
    });
  } catch (error: any) {
    console.error("Server error:", error?.message || error);
    res.status(500).json({
      status: 500,
      message: "Server error",
      error: error?.message || "Unknown error",
    });
  }
};

export { createBook };