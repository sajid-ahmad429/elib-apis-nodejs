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
        filename_override: `${Date.now()}_${file.originalname}`,
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
 * Deletes a file from Cloudinary based on its URL.
 */
const deleteFromCloudinary = async (url: string) => {
  try {
    if (!url.includes("res.cloudinary.com")) {
      console.warn("URL is not a Cloudinary URL, skipping deletion:", url);
      return;
    }

    const matched = url.match(/\/v\d+\/(.+?)\.(jpg|jpeg|png|gif|webp|pdf)/);
    if (!matched || !matched[1]) {
      console.warn("Could not extract public ID from URL:", url);
      return;
    }

    const publicId = matched[1]; // e.g., books/coverImages/filename
    const resourceType = url.endsWith(".pdf") ? "raw" : "image";

    await cloudinary.uploader.destroy(publicId, { resource_type: resourceType });
    console.log(`Successfully deleted ${publicId} from Cloudinary`);
  } catch (err) {
    console.error("Failed to delete from Cloudinary:", err);
  }
};

const getAllBooks = async (req: Request, res: Response): Promise<void> => {
  try {
    const {
      page = "1",
      limit = "10",
      search = "",
      sortBy = "createdAt",
      sortOrder = "desc",
      status,
      category,
    } = req.query;

    const pageNum = parseInt(page as string, 10);
    const limitNum = parseInt(limit as string, 10);

    // === Build Query Conditions ===
    const query: any = {};

    if (search) {
      const regex = new RegExp(search.toString(), "i"); // case-insensitive
      query.$or = [
        { title: regex },
        { isbn: regex },
        { genre: regex },
        { category: regex },
        { publisher: regex },
      ];
    }

    if (status) {
      query.status = status;
    }

    if (category) {
      query.category = category;
    }

    // === Sorting ===
    const sort: { [key: string]: 1 | -1 } = {};
    sort[sortBy as string] = sortOrder === "asc" ? 1 : -1;

    const total = await Book.countDocuments(query);
    const books = await Book.find(query)
      .sort(sort)
      .skip((pageNum - 1) * limitNum)
      .limit(limitNum);

    res.status(200).json({
      status: 200,
      message: "Books retrieved successfully",
      meta: {
        totalRecords: total,
        currentPage: pageNum,
        totalPages: Math.ceil(total / limitNum),
        perPage: limitNum,
      },
      data: books,
    });
  } catch (error: any) {
    console.error("Error fetching books:", error?.message || error);
    res.status(500).json({
      status: 500,
      message: "Server error",
      error: error?.message || "Unknown error",
    });
  }
};

const getBookById = async (req: Request, res: Response): Promise<void> => {
  const { id } = req.params;

  try {
    // Optionally validate MongoDB ObjectId
    if (!id || id.length !== 24) {
      res.status(400).json({ status: 400, message: "Invalid book ID" });
      return;
    }

    const book = await Book.findById(id)
      // .populate("author", "name email") // Uncomment if author is ref
      .lean(); // lean returns a plain JS object

    if (!book) {
      res.status(404).json({ status: 404, message: "Book not found" });
      return;
    }

    res.status(200).json({
      status: 200,
      message: "Book retrieved successfully",
      data: book,
    });
  } catch (error: any) {
    console.error("Error fetching book by ID:", error?.message || error);
    res.status(500).json({
      status: 500,
      message: "Server error",
      error: error?.message || "Unknown error",
    });
  }
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
      author: (req as any).userId, // Assuming userId is attached to the request by a middleware
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
    console.error("Server error during book creation:", error?.message || error);
    res.status(500).json({
      status: 500,
      message: "Server error",
      error: error?.message || "Unknown error",
    });
  }
};

/**
 * Updates a book record with new fields and files.
 */
const updateBook = async (req: Request, res: Response): Promise<void> => {
  const { id } = req.params;
  const errors = validationResult(req);

  if (!errors.isEmpty()) {
    res.status(422).json({ status: 422, message: "Validation failed", errors: errors.array() });
    return;
  }

  try {
    const book = await Book.findById(id);
    if (!book) {
      res.status(404).json({ status: 404, message: "Book not found" });
      return;
    }

    const {
      title = "",
      isbn = "",
      publishedDate,
      category = "",
      language = "",
      pages,
      publisher = "",
      price,
      status = "available",
      genre = "",
    } = req.body;

    const parsedDate = new Date(publishedDate);
    if (isNaN(parsedDate.getTime())) {
      res.status(400).json({ status: 400, message: "Invalid published date" });
      return;
    }

    // Check for duplicate ISBN if it's being changed
    if (isbn.trim() && isbn.trim() !== book.isbn) {
      const existingBookWithIsbn = await Book.findOne({ isbn: isbn.trim() });
      if (existingBookWithIsbn && existingBookWithIsbn._id.toString() !== id) {
        res.status(400).json({ status: 400, message: "Book with this ISBN already exists" });
        return;
      }
    }

    const files = req.files as { [fieldname: string]: Express.Multer.File[] } | undefined;
    let coverImageUrl = book.coverImage;
    let bookFileUrl = book.file;

    // === Cover Image ===
    if (files?.coverImage?.[0]) {
      const newCoverImage = files.coverImage[0];
      if (!allowedImageMimeTypes.includes(newCoverImage.mimetype)) {
        res.status(400).json({
          status: 400,
          message: "Invalid cover image type. Allowed formats: JPEG, PNG, GIF, WEBP",
        });
        return;
      }

      if (coverImageUrl) await deleteFromCloudinary(coverImageUrl);
      coverImageUrl = await uploadToCloudinary(newCoverImage, "books/coverImages");
    }

    // === Book PDF File ===
    if (files?.file?.[0]) {
      const newBookFile = files.file[0];
      if (!allowedFileMimeTypes.includes(newBookFile.mimetype)) {
        res.status(400).json({
          status: 400,
          message: "Invalid book file type. Only PDF files are allowed",
        });
        return;
      }

      if (bookFileUrl) await deleteFromCloudinary(bookFileUrl);
      bookFileUrl = await uploadToCloudinary(newBookFile, "books/files");
    }

    // Update all fields
    book.set({
      title: title.trim(),
      genre: genre.trim(),
      isbn: isbn.trim(),
      publishedDate: parsedDate,
      category: category.trim(),
      language: language.trim(),
      pages: pages !== undefined ? Number(pages) : book.pages,
      publisher: publisher.trim(),
      price: price !== undefined ? Number(price) : book.price,
      status: status,
      coverImage: coverImageUrl,
      file: bookFileUrl,
    });

    await book.save();

    res.status(200).json({ status: 200, message: "Book updated successfully", data: book });
  } catch (error: any) {
    console.error("Server error during book update:", error?.message || error);
    res.status(500).json({ status: 500, message: "Server error", error: error?.message || "Unknown error" });
  }
};

const patchBook = async (req: Request, res: Response): Promise<void> => {
  const { id } = req.params;
  const errors = validationResult(req);

  if (!errors.isEmpty()) {
    res.status(422).json({ status: 422, message: "Validation failed", errors: errors.array() });
    return;
  }

  try {
    const book = await Book.findById(id);
    if (!book) {
      res.status(404).json({ status: 404, message: "Book not found" });
      return;
    }

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

    // === Validate publishedDate if provided ===
    let parsedDate;
    if (publishedDate !== undefined) {
      parsedDate = new Date(publishedDate);
      if (isNaN(parsedDate.getTime())) {
        res.status(400).json({ status: 400, message: "Invalid published date" });
        return;
      }
    }

    // === Check for duplicate ISBN if changed ===
    if (isbn && isbn.trim() !== book.isbn) {
      const existingBookWithIsbn = await Book.findOne({ isbn: isbn.trim() });
      if (existingBookWithIsbn && existingBookWithIsbn._id.toString() !== id) {
        res.status(400).json({ status: 400, message: "Book with this ISBN already exists" });
        return;
      }
    }

    // === Handle uploaded files ===
    const files = req.files as { [fieldname: string]: Express.Multer.File[] } | undefined;
    let coverImageUrl = book.coverImage;
    let bookFileUrl = book.file;

    // === Cover Image ===
    if (files?.coverImage?.[0]) {
      const newCoverImage = files.coverImage[0];
      if (!allowedImageMimeTypes.includes(newCoverImage.mimetype)) {
        res.status(400).json({
          status: 400,
          message: "Invalid cover image type. Allowed formats: JPEG, PNG, GIF, WEBP",
        });
        return;
      }

      if (coverImageUrl) await deleteFromCloudinary(coverImageUrl);
      coverImageUrl = await uploadToCloudinary(newCoverImage, "books/coverImages");
    }

    // === Book PDF File ===
    if (files?.file?.[0]) {
      const newBookFile = files.file[0];
      if (!allowedFileMimeTypes.includes(newBookFile.mimetype)) {
        res.status(400).json({
          status: 400,
          message: "Invalid book file type. Only PDF files are allowed",
        });
        return;
      }

      if (bookFileUrl) await deleteFromCloudinary(bookFileUrl);
      bookFileUrl = await uploadToCloudinary(newBookFile, "books/files");
    }

    // === Only update fields that are provided ===
    if (title !== undefined) book.title = title.trim();
    if (genre !== undefined) book.genre = genre.trim();
    if (isbn !== undefined) book.isbn = isbn.trim();
    if (publishedDate !== undefined) book.publishedDate = parsedDate!;
    if (category !== undefined) book.category = category.trim();
    if (language !== undefined) book.language = language.trim();
    if (pages !== undefined) book.pages = Number(pages);
    if (publisher !== undefined) book.publisher = publisher.trim();
    if (price !== undefined) book.price = Number(price);
    if (status !== undefined) book.status = status;
    if (coverImageUrl !== book.coverImage) book.coverImage = coverImageUrl;
    if (bookFileUrl !== book.file) book.file = bookFileUrl;

    await book.save();

    res.status(200).json({ status: 200, message: "Book patched successfully", data: book });
  } catch (error: any) {
    console.error("Server error during book patch:", error?.message || error);
    res.status(500).json({ status: 500, message: "Server error", error: error?.message || "Unknown error" });
  }
};

const deleteBook = async (req: Request, res: Response): Promise<void> => {
  const { id } = req.params;

  try {
    const book = await Book.findById(id);
    if (!book) {
      res.status(404).json({ status: 404, message: "Book not found" });
      return;
    }

    // Optional: delete images/files from Cloudinary
    if (book.coverImage) {
      await deleteFromCloudinary(book.coverImage);
    }

    if (book.file) {
      await deleteFromCloudinary(book.file);
    }

    await Book.findByIdAndDelete(id);

    res.status(200).json({ status: 200, message: "Book deleted successfully" });
  } catch (error: any) {
    console.error("Server error during book deletion:", error?.message || error);
    res.status(500).json({ status: 500, message: "Server error", error: error?.message || "Unknown error" });
  }
};

export { createBook, updateBook, patchBook, deleteBook, getAllBooks, getBookById };
